<?php

namespace App\Jobs;

use App\Telegram\TelegramUpdateDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTelegramUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $payload
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TelegramUpdateDispatcher $dispatcher): void
    {
        $dispatcher->dispatch($this->payload);
    }
}
