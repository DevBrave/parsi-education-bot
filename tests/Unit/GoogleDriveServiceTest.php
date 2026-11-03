<?php

namespace Tests\Unit;

use App\Services\GoogleDriveService;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class GoogleDriveServiceTest extends TestCase
{
    public function test_deleting_file_clears_reservation_for_future_uploads(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('drive/uploads/test.json', json_encode(['id' => 'file-id']));
        $history = [];
        $service = $this->configuredService([new Response(204)], $history);
        $this->assertTrue($service->deleteFile('file-id'));
        Storage::disk('local')->assertMissing('drive/uploads/test.json');
    }

    private function configuredService(array $responses, array &$history): GoogleDriveService
    {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($history));
        $client = new GoogleClient(['retry' => ['retries' => 0]]);
        $client->setHttpClient(new Client(['handler' => $handler]));
        $client->setAccessToken(['access_token' => 'test-token', 'created' => time(), 'expires_in' => 3600]);

        return new class($client) extends GoogleDriveService
        {
            public function __construct(GoogleClient $client)
            {
                parent::__construct();
                $this->client = $client;
                $this->driveService = new Drive($client);
                $this->initialized = true;
            }
        };
    }

    public function test_folder_failure_does_not_return_parent_as_success(): void
    {
        $history = [];
        $service = $this->configuredService([new Response(403, [], '{"error":{"message":"Forbidden","code":403}}')], $history);
        $this->assertNull($service->getOrCreateFolder('Physics', 'parent-id'));
        $this->assertCount(1, $history);
    }

    public function test_folder_search_is_scoped_to_root_and_escapes_names(): void
    {
        config(['google.folder_id' => null]);
        $history = [];
        $service = $this->configuredService([new Response(200, [], '{"files":[{"id":"folder-id"}]}')], $history);
        $this->assertSame('folder-id', $service->getOrCreateFolder("Physics\\Bob's"));
        parse_str($history[0]['request']->getUri()->getQuery(), $parameters);
        $this->assertStringContainsString("'root' in parents", $parameters['q']);
        $this->assertStringContainsString("name = 'Physics\\\\Bob\\'s'", $parameters['q']);
    }

    public function test_sharing_retry_reuses_uploaded_file_across_service_instances(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('source.pdf', '%PDF-test');
        $history = [];
        $service = $this->configuredService([
            new Response(200, [], '{"ids":["reserved-id"]}'),
            new Response(200, [], '{"id":"reserved-id"}'),
            new Response(403, [], '{"error":{"message":"Sharing denied","code":403}}'),
        ], $history);
        $path = Storage::disk('local')->path('source.pdf');
        $this->assertNull($service->uploadFile($path, 'source.pdf', 'application/pdf', 'parent-id'));
        Storage::disk('local')->assertExists('source.pdf');
        $this->assertStringContainsString('reserved-id', (string) $history[1]['request']->getBody());

        $retryHistory = [];
        $retry = $this->configuredService([
            new Response(200, [], '{"id":"reserved-id","trashed":false}'),
            new Response(200, [], '{"id":"permission-id"}'),
        ], $retryHistory);
        $this->assertSame('reserved-id', $retry->uploadFile($path, 'source.pdf', 'application/pdf', 'parent-id'));
        $this->assertCount(2, $retryHistory);
        $this->assertSame('GET', $retryHistory[0]['request']->getMethod());
        $this->assertStringContainsString('/reserved-id/permissions', $retryHistory[1]['request']->getUri()->getPath());
    }

    public function test_interrupted_upload_reuses_reserved_id(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('source.pdf', '%PDF-test');
        $history = [];
        $service = $this->configuredService([
            new Response(200, [], '{"ids":["reserved-id"]}'),
            new Response(503, [], '{"error":{"message":"Unavailable","code":503}}'),
            new Response(404, [], '{"error":{"message":"Not found","code":404}}'),
            new Response(200, [], '{"id":"reserved-id"}'),
            new Response(200, [], '{"id":"permission-id"}'),
        ], $history);
        $path = Storage::disk('local')->path('source.pdf');
        $this->assertNull($service->uploadFile($path, 'source.pdf', 'application/pdf'));
        $this->assertSame('reserved-id', $service->uploadFile($path, 'source.pdf', 'application/pdf'));
        $this->assertStringContainsString('reserved-id', (string) $history[1]['request']->getBody());
        $this->assertStringContainsString('reserved-id', (string) $history[3]['request']->getBody());
        $this->assertCount(5, $history);
    }

    public function test_temporary_initialization_failure_can_retry_after_cooldown(): void
    {
        config(['google.client_id' => 'id', 'google.client_secret' => 'secret', 'google.refresh_token' => 'refresh']);
        $client = Mockery::mock(GoogleClient::class);
        $client->shouldReceive('setClientId')->twice();
        $client->shouldReceive('setClientSecret')->twice();
        $client->shouldReceive('fetchAccessTokenWithRefreshToken')->once()->ordered()->andThrow(new \RuntimeException('Timeout'));
        $client->shouldReceive('fetchAccessTokenWithRefreshToken')->once()->ordered()->andReturn(['access_token' => 'token']);
        $service = new class($client) extends GoogleDriveService
        {
            public function __construct(private GoogleClient $testClient)
            {
                parent::__construct();
            }

            protected function createClient(): GoogleClient
            {
                return $this->testClient;
            }
        };
        $this->assertFalse($service->isConfigured());
        $this->assertFalse($service->isConfigured());
        $this->travel(31)->seconds();
        try {
            $this->assertTrue($service->isConfigured());
        } finally {
            $this->travelBack();
        }
    }

    public function test_large_upload_uses_chunks_and_restores_client_before_sharing(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('large.pdf', str_repeat('x', 5 * 1024 * 1024));
        $history = [];
        $service = $this->configuredService([
            new Response(200, [], '{"ids":["large-id"]}'),
            new Response(200, ['Location' => 'https://upload.example.test/session']),
            new Response(308, ['Range' => 'bytes=0-2097151']),
            new Response(308, ['Range' => 'bytes=0-4194303']),
            new Response(200, [], '{"id":"large-id"}'),
            new Response(200, [], '{"id":"permission-id"}'),
        ], $history);
        $this->assertSame('large-id', $service->uploadFile(Storage::disk('local')->path('large.pdf'), 'large.pdf', 'application/pdf'));
        $this->assertCount(6, $history);
        $this->assertSame('bytes 0-2097151/5242880', $history[2]['request']->getHeaderLine('Content-Range'));
        $this->assertSame('bytes 4194304-5242879/5242880', $history[4]['request']->getHeaderLine('Content-Range'));
        $this->assertStringContainsString('/large-id/permissions', $history[5]['request']->getUri()->getPath());
    }

    public function test_oauth_failure_does_not_fall_back_to_service_account(): void
    {
        config([
            'google.client_id' => 'test-client',
            'google.client_secret' => 'test-secret',
            'google.refresh_token' => 'test-refresh',
            'google.service_account_json' => __FILE__,
        ]);
        foreach (['exception', 'rejected'] as $failure) {
            $client = Mockery::mock(GoogleClient::class);
            $client->shouldNotReceive('setAuthConfig');
            $client->shouldReceive('setClientId')->once();
            $client->shouldReceive('setClientSecret')->once();
            if ($failure === 'exception') {
                $client->shouldReceive('fetchAccessTokenWithRefreshToken')->once()->andThrow(new \RuntimeException('DNS timeout'));
            } else {
                $client->shouldReceive('fetchAccessTokenWithRefreshToken')->once()->andReturn(['error' => 'invalid_grant']);
            }
            $service = new class($client) extends GoogleDriveService
            {
                public function __construct(private GoogleClient $testClient)
                {
                    parent::__construct();
                }

                protected function createClient(): GoogleClient
                {
                    return $this->testClient;
                }
            };
            $this->assertFalse($service->isConfigured());
            $this->assertFalse($service->isConfigured());
        }
    }

    public function test_constructor_does_not_initialize_google_client(): void
    {
        $service = new class extends GoogleDriveService
        {
            public int $initializations = 0;

            protected function initialize(): void
            {
                $this->initializations++;
                $this->initialized = true;
            }
        };

        $this->assertSame(0, $service->initializations);
        $this->assertFalse($service->isConfigured());
        $this->assertFalse($service->isConfigured());
        $this->assertSame(1, $service->initializations);
    }

    public function test_is_configured_returns_false_when_unconfigured(): void
    {
        config([
            'google.client_id' => null,
            'google.client_secret' => null,
            'google.refresh_token' => null,
            'google.service_account_json' => null,
        ]);

        $service = new GoogleDriveService;
        $this->assertFalse($service->isConfigured());
    }

    public function test_upload_file_returns_null_when_unconfigured(): void
    {
        config([
            'google.client_id' => null,
            'google.client_secret' => null,
            'google.refresh_token' => null,
            'google.service_account_json' => null,
        ]);

        $service = new GoogleDriveService;
        $result = $service->uploadFile(__FILE__, 'test.php', 'text/x-php');
        $this->assertNull($result);
    }

    public function test_upload_file_returns_null_for_nonexistent_file(): void
    {
        config([
            'google.client_id' => null,
            'google.client_secret' => null,
            'google.refresh_token' => null,
            'google.service_account_json' => null,
        ]);
        $service = new GoogleDriveService;
        $result = $service->uploadFile('/path/to/nonexistent/file.pdf', 'test.pdf', 'application/pdf');
        $this->assertNull($result);
    }
}
