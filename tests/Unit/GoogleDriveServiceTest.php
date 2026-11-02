<?php

namespace Tests\Unit;

use App\Services\GoogleDriveService;
use Tests\TestCase;

class GoogleDriveServiceTest extends TestCase
{
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
