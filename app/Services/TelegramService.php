<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;

    protected string $baseUrl;

    public function __construct()
    {
        $this->botToken = (string) config('telegram.bot_token');
        $this->baseUrl = config('telegram.api_url').$this->botToken;
    }

    /**
     * Get an HTTP client instance configured with timeout and optional proxy.
     */
    protected function client(int $timeout = 15, int $connectTimeout = 8): PendingRequest
    {
        $client = Http::timeout($timeout)->connectTimeout($connectTimeout);

        $proxy = config('telegram.proxy') ?: env('TELEGRAM_PROXY') ?: env('HTTPS_PROXY') ?: env('HTTP_PROXY');
        if (! empty($proxy)) {
            $client = $client->withOptions(['proxy' => $proxy]);
        }

        return $client;
    }

    /**
     * Send a text message to a Telegram chat.
     */
    public function sendMessage(
        int|string $chatId,
        string $text,
        ?array $replyMarkup = null,
        string $parseMode = 'HTML'
    ): Response {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $response = $this->client()->post("{$this->baseUrl}/sendMessage", $payload);

        if ($response->failed()) {
            Log::error('Telegram sendMessage failed', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        }

        return $response;
    }

    /**
     * Register the webhook with Telegram API.
     */
    public function setWebhook(string $url, ?string $secretToken = null): Response
    {
        $payload = [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query', 'inline_query'],
        ];

        if ($secretToken !== null) {
            $payload['secret_token'] = $secretToken;
        }

        return $this->client()->post("{$this->baseUrl}/setWebhook", $payload);
    }

    /**
     * Get webhook info status from Telegram.
     */
    public function getWebhookInfo(): Response
    {
        return $this->client()->get("{$this->baseUrl}/getWebhookInfo");
    }

    /**
     * Edit an existing message's text and optional inline keyboard.
     */
    public function editMessageText(
        int|string $chatId,
        int $messageId,
        string $text,
        ?array $replyMarkup = null,
        string $parseMode = 'HTML'
    ): Response {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $response = $this->client()->post("{$this->baseUrl}/editMessageText", $payload);

        if ($response->failed()) {
            Log::error('Telegram editMessageText failed', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        }

        return $response;
    }

    /**
     * Edit an existing message caption (for documents, photos, media).
     */
    public function editMessageCaption(
        int|string $chatId,
        int $messageId,
        string $caption,
        ?array $replyMarkup = null,
        string $parseMode = 'HTML'
    ): Response {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'caption' => $caption,
            'parse_mode' => $parseMode,
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $response = $this->client()->post("{$this->baseUrl}/editMessageCaption", $payload);

        if ($response->failed()) {
            Log::error('Telegram editMessageCaption failed', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        }

        return $response;
    }

    /**
     * Acknowledge an inline button press (removes the loading spinner).
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): ?Response
    {
        $payload = [
            'callback_query_id' => $callbackQueryId,
        ];

        if ($text !== null) {
            $payload['text'] = $text;
        }

        try {
            return $this->client(5, 3)->post("{$this->baseUrl}/answerCallbackQuery", $payload);
        } catch (\Throwable $e) {
            Log::warning('Telegram answerCallbackQuery failed or timed out: '.$e->getMessage(), [
                'callback_query_id' => $callbackQueryId,
            ]);

            return null;
        }
    }

    /**
     * Answer an inline query (for typing @botusername query in any chat).
     */
    public function answerInlineQuery(string $inlineQueryId, array $results, int $cacheTime = 60): ?Response
    {
        $payload = [
            'inline_query_id' => $inlineQueryId,
            'results' => json_encode($results),
            'cache_time' => $cacheTime,
        ];

        try {
            $response = $this->client(5, 3)->post("{$this->baseUrl}/answerInlineQuery", $payload);
            if (! $response->json('ok')) {
                Log::error('Telegram answerInlineQuery rejected by Telegram API', [
                    'inline_query_id' => $inlineQueryId,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
            }

            return $response;
        } catch (\Throwable $e) {
            Log::warning('Telegram answerInlineQuery failed: '.$e->getMessage(), [
                'inline_query_id' => $inlineQueryId,
            ]);

            return null;
        }
    }

    /**
     * Upload and send a document from a local file path to Telegram.
     * Returns file_id and file_unique_id on success.
     */
    public function uploadDocument(
        string|int $chatId,
        string $filePath,
        string $filename,
        ?string $caption = null,
        string $parseMode = 'HTML'
    ): ?array {
        $payload = [
            'chat_id' => $chatId,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
            $payload['parse_mode'] = $parseMode;
        }

        $response = $this->client(120, 15)
            ->attach('document', file_get_contents($filePath), $filename)
            ->post("{$this->baseUrl}/sendDocument", $payload);

        if (! $response->json('ok')) {
            Log::error('Telegram uploadDocument failed', [
                'chat_id' => $chatId,
                'response' => $response->json(),
            ]);

            return null;
        }

        $document = $response->json('result.document');

        return [
            'file_id' => $document['file_id'] ?? null,
            'file_unique_id' => $document['file_unique_id'] ?? null,
            'message_id' => $response->json('result.message_id'),
        ];
    }

    /**
     * Send an existing Telegram document by file_id with optional caption.
     */
    public function sendDocument(
        int|string $chatId,
        string $fileId,
        ?string $caption = null,
        string $parseMode = 'HTML',
        ?array $replyMarkup = null
    ): Response {
        $payload = [
            'chat_id' => $chatId,
            'document' => $fileId,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
            $payload['parse_mode'] = $parseMode;
        }

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $response = $this->client()->post("{$this->baseUrl}/sendDocument", $payload);
        if (! $response->json('ok')) {
            Log::error('Telegram sendDocument failed', [
                'chat_id' => $chatId,
                'description' => $response->json('description'),
            ]);
        }

        return $response;
    }

    /**
     * Send an existing Telegram photo by file_id with optional caption.
     */
    public function sendPhoto(
        int|string $chatId,
        string $fileId,
        ?string $caption = null,
        string $parseMode = 'HTML',
        ?array $replyMarkup = null
    ): Response {
        $payload = [
            'chat_id' => $chatId,
            'photo' => $fileId,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
            $payload['parse_mode'] = $parseMode;
        }

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $response = $this->client()->post("{$this->baseUrl}/sendPhoto", $payload);
        if (! $response->json('ok')) {
            Log::error('Telegram sendPhoto failed', [
                'chat_id' => $chatId,
                'description' => $response->json('description'),
            ]);
        }

        return $response;
    }

    public function getMe(): Response
    {
        return $this->client()->get("{$this->baseUrl}/getMe");
    }

    /**
     * Retrieve file metadata (including file_path) from Telegram.
     */
    public function getFile(string $fileId): ?array
    {
        $response = $this->client()->post("{$this->baseUrl}/getFile", [
            'file_id' => $fileId,
        ]);

        if ($response->json('ok')) {
            return $response->json('result');
        }

        Log::error('Telegram getFile failed', [
            'file_id' => $fileId,
            'response' => $response->json(),
        ]);

        return null;
    }

    /**
     * Generate the direct download URL for a file using its Telegram file_path.
     */
    public function getFileDownloadUrl(string $filePath): string
    {
        return "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";
    }

    /**
     * Delete a message from a chat or channel by its message ID.
     */
    public function deleteMessage(int|string $chatId, int|string $messageId): Response
    {
        $response = $this->client()->post("{$this->baseUrl}/deleteMessage", [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);

        if (! $response->json('ok')) {
            Log::warning('Telegram deleteMessage failed', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'response' => $response->json(),
            ]);
        }

        return $response;
    }
}
