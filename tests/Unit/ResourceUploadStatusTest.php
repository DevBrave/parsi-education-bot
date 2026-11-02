<?php

namespace Tests\Unit;

use App\Models\Resource;
use App\Models\ResourceFile;
use App\Services\GoogleDriveService;
use App\Services\ResourceUploadService;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ResourceUploadStatusTest extends TestCase
{
    public function test_telegram_status_is_saved_before_drive_starts(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('resources/test.pdf', '%PDF-test');
        $resource = new Resource(['status' => 'approved', 'title' => 'Test']);
        $resource->setRelations(['course' => null, 'resourceType' => null, 'submitter' => null]);
        $file = Mockery::mock(ResourceFile::class)->makePartial();
        $file->forceFill(['id' => 1, 'resource_id' => 1, 'original_filename' => 'test.pdf']);
        $file->setRelation('resource', $resource);
        $file->shouldReceive('fresh')->andReturn($file);
        $file->shouldReceive('update')->twice()->andReturnUsing(function ($attributes) use ($file) {
            $file->forceFill($attributes);

            return true;
        });
        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('uploadDocument')->once()->andReturn([
            'file_id' => 'telegram-id', 'file_unique_id' => 'unique-id', 'message_id' => 42,
        ]);
        $drive = Mockery::mock(GoogleDriveService::class);
        $drive->shouldReceive('isConfigured')->once()->andReturnUsing(function () use ($file) {
            $this->assertSame('telegram-id', $file->telegram_file_id);
            $this->assertSame(42, $file->telegram_message_id);

            return false;
        });

        $this->assertTrue((new ResourceUploadService($telegram, $drive))->sync($file, 'resources/test.pdf'));
        Storage::disk('local')->assertExists('resources/test.pdf');
        $this->assertSame('resources/test.pdf', $file->local_path);
    }
}
