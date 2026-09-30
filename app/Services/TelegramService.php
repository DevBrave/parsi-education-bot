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
     * Test bot token validity.
     */
    public function getMe(): Response
    {
        return Http::get("{$this->baseUrl}/getMe");
    }
}
