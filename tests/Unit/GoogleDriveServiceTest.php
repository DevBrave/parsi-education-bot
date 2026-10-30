<?php

namespace Tests\Unit;

use App\Services\GoogleDriveService;
use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Tests\TestCase;

class GoogleDriveServiceTest extends TestCase
{
    public function test_is_configured_returns_false_when_unconfigured(): void
    {
        config([
            'google.client_id' => null,
            'google.client_secret' => null,
            'google.refresh_token' => null,
            'google.service_account_json' => null,
        ]);

        $service = new GoogleDriveService();
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

        $service = new GoogleDriveService();
        $result = $service->uploadFile(__FILE__, 'test.php', 'text/x-php');
        $this->assertNull($result);
    }

    public function test_upload_file_returns_null_for_nonexistent_file(): void
    {
        $service = new GoogleDriveService();
        $result = $service->uploadFile('/path/to/nonexistent/file.pdf', 'test.pdf', 'application/pdf');
        $this->assertNull($result);
    }
}

