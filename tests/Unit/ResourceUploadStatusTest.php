<?php

namespace Tests\Unit;

use App\Models\Resource;
use App\Models\ResourceFile;
use App\Services\GoogleDriveService;
use App\Services\ResourceUploadService;
use App\Services\TelegramService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ResourceUploadStatusTest extends TestCase
{
    public function test_drive_only_upload_posts_download_link_to_telegram_channel(): void
    {
        config(['telegram.storage_channel_id' => '-100123', 'telegram.max_upload_size_mb' => 0]);
        Storage::fake('local');
        Storage::disk('local')->put('resources/large.pdf', '%PDF-test');
        $resource = new Resource(['status' => 'approved', 'title' => 'Large file']);
        $resource->setRelations(['course' => null, 'resourceType' => null, 'submitter' => null]);
        $file = Mockery::mock(ResourceFile::class)->makePartial();
        $file->forceFill(['id' => 2, 'resource_id' => 2, 'original_filename' => 'large.pdf', 'mime_type' => 'application/pdf']);
        $file->setRelation('resource', $resource);
        $file->shouldReceive('fresh')->andReturn($file);
        $file->shouldReceive('update')->once()->andReturnUsing(function ($attributes) use ($file) {
            $file->forceFill($attributes);

            return true;
        });
        $telegram = Mockery::mock(TelegramService::class);
        $telegram->shouldNotReceive('uploadDocument');
        $telegram->shouldReceive('sendMessage')->once()->withArgs(function ($chat, $text, $markup) {
            $this->assertSame('-100123', $chat);
            $this->assertStringContainsString('https://drive.google.com/file/d/drive-id/view', $text);
            $this->assertSame('https://drive.google.com/file/d/drive-id/view', $markup['inline_keyboard'][0][0]['url']);

            return true;
        })->andReturn(new Response(new \GuzzleHttp\Psr7\Response(200, [], json_encode(['ok' => true, 'result' => ['message_id' => 99]]))));
        $drive = Mockery::mock(GoogleDriveService::class);
        $drive->shouldReceive('isConfigured')->once()->andReturn(true);
        $drive->shouldReceive('getOrCreateFolder')->twice()->andReturn('folder');
        $drive->shouldReceive('uploadFile')->once()->andReturn('drive-id');

        $this->assertTrue((new ResourceUploadService($telegram, $drive))->sync($file, 'resources/large.pdf'));
        $this->assertNull($file->telegram_file_id);
        $this->assertSame(99, $file->telegram_message_id);
    }

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
