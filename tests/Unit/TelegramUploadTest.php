<?php

namespace Tests\Unit;

use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramUploadTest extends TestCase
{
    public function test_small_document_uses_cloud_endpoint_and_short_timeout(): void
    {
        config(['telegram.api_url' => 'https://api.telegram.org/bot', 'telegram.bot_token' => 'test-token']);
        $options = null;
        Http::fake(function ($request, $requestOptions) use (&$options) {
            $options = $requestOptions;

            return Http::response(['ok' => true, 'result' => [
                'message_id' => 42,
                'document' => ['file_id' => 'document-id', 'file_unique_id' => 'unique-id'],
            ]]);
        });
        $path = tempnam(sys_get_temp_dir(), 'telegram-test-');
        file_put_contents($path, '%PDF-test');
        try {
            $result = (new TelegramService)->uploadDocument(123, $path, 'test.pdf');
            $this->assertSame('document-id', $result['file_id']);
            $this->assertSame(60, $options['timeout']);
            $this->assertSame(10, $options['connect_timeout']);
            Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bottest-token/sendDocument');
        } finally {
            unlink($path);
        }
    }
}
