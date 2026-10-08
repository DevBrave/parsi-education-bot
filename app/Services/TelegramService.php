<?php

namespace App\Services;

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
        $this->baseUrl = config('telegram.api_url') . $this->botToken;
    }

    /**
     * Send a text message to a Telegram chat.
     */
    public function sendMessage(
        int $chatId,
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

        $response = Http::post("{$this->baseUrl}/sendMessage", $payload);

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
            'allowed_updates' => ['message', 'callback_query'],
        ];

        if ($secretToken !== null) {
            $payload['secret_token'] = $secretToken;
        }

        return Http::post("{$this->baseUrl}/setWebhook", $payload);
    }

    /**
     * Get webhook info status from Telegram.
     */
    public function getWebhookInfo(): Response
    {
        return Http::get("{$this->baseUrl}/getWebhookInfo");
    }

    /**
     * Edit an existing message's text and optional inline keyboard.
     */
    public function editMessageText(
        int $chatId,
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

        $response = Http::post("{$this->baseUrl}/editMessageText", $payload);

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
     * Acknowledge an inline button press (removes the loading spinner).
     */
    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): Response
    {
        $payload = [
            'callback_query_id' => $callbackQueryId,
        ];

        if ($text !== null) {
            $payload['text'] = $text;
        }
        

        return Http::post("{$this->baseUrl}/answerCallbackQuery", $payload);
    }

    /**
     * Upload and send a document from a local file path to Telegram.
     * Returns file_id and file_unique_id on success.
     */
    public function uploadDocument(string|int $chatId, string $filePath, string $filename, ?string $caption = null): ?array
    {
        $response = Http::timeout(120)
            ->attach('document', file_get_contents($filePath), $filename)
            ->post("{$this->baseUrl}/sendDocument", array_filter([
                'chat_id' => $chatId,
                'caption' => $caption,
            ]));

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
        int $chatId,
        string $fileId,
        ?string $caption = null,
        string $parseMode = 'HTML'
    ): Response {
        $payload = [
            'chat_id' => $chatId,
            'document' => $fileId,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
            $payload['parse_mode'] = $parseMode;
        }
        
        $response = Http::post("{$this->baseUrl}/sendDocument", $payload);
        if (! $response->json('ok')) {
            Log::error('Telegram sendDocument failed', [
                'chat_id' => $chatId,
                'description' => $response->json('description'),
            ]);
        }

        return $response;
    }

    public function getMe(): Response
    {
        return Http::get("{$this->baseUrl}/getMe");
    }
}
