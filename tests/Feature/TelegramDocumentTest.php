<?php

namespace Tests\Feature;

use App\Services\TelegramService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TelegramDocumentTest extends TestCase
{
    public function test_document_is_sent_using_the_telegram_file_id(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        $response = app(TelegramService::class)->sendDocument(42, 'telegram-file-id');

        $this->assertTrue($response->json('ok'));
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/sendDocument')
            && $request['chat_id'] === 42
            && $request['document'] === 'telegram-file-id');
        Http::assertSentCount(1);
    }

    public function test_telegram_failure_is_logged_and_returned(): void
    {
        Http::fake(['*' => Http::response(['ok' => false, 'description' => 'Invalid file'], 400)]);
        Log::shouldReceive('error')->once()->with('Telegram sendDocument failed', [
            'chat_id' => 42,
            'description' => 'Invalid file',
        ]);

        $response = app(TelegramService::class)->sendDocument(42, 'invalid-file');

        $this->assertFalse($response->json('ok'));
    }
}
