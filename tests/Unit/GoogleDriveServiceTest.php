<?php

namespace Tests\Unit;

use App\Services\GoogleDriveService;
use Google\Client as GoogleClient;
use Mockery;
use Tests\TestCase;

class GoogleDriveServiceTest extends TestCase
{
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
