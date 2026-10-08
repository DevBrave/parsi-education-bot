<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'telegram:set-webhook {url? : The full HTTPS webhook URL (e.g. https://xyz.trycloudflare.com/api/telegram/webhook)}';

    /**
     * The console command description.
     */
    protected $description = 'Set or inspect the Telegram Bot webhook';

    public function handle(TelegramService $telegram): int
    {
        $url = $this->argument('url');

        if (empty($url)) {
            $this->info('Fetching current webhook status from Telegram...');
            $info = $telegram->getWebhookInfo();
            $this->line(json_encode($info->json(), JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $secret = config('telegram.webhook_secret');
        $this->info("Setting webhook to: {$url}");

        $response = $telegram->setWebhook($url, ! empty($secret) ? $secret : null);

        if ($response->successful()) {
            $this->info('✅ Webhook successfully configured with Telegram!');
            $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->error('❌ Failed to set webhook with Telegram:');
        $this->line(json_encode($response->json(), JSON_PRETTY_PRINT));

        return self::FAILURE;
    }
}
