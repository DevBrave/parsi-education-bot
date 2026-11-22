<?php

namespace App\Jobs;

use App\Models\AnnouncementDelivery;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Str;

class SendAnnouncementDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 25;

    public bool $failOnTimeout = true;

    private string $claimToken;

    public function __construct(public int $deliveryId)
    {
        $this->claimToken = (string) Str::uuid();
    }

    public function handle(TelegramService $telegram): void
    {
        $claimed = AnnouncementDelivery::query()
            ->whereKey($this->deliveryId)
            ->where('status', AnnouncementDelivery::PENDING)
            ->where(fn ($query) => $query->whereNull('retry_at')->orWhere('retry_at', '<=', now()))
            ->update(['status' => AnnouncementDelivery::SENDING, 'claim_token' => $this->claimToken]);
        if ($claimed !== 1) {
            if ($this->attempts() > 1) {
                $this->failed();
            }

            return;
        }

        $delivery = AnnouncementDelivery::query()->with('announcement')->find($this->deliveryId);
        $announcement = $delivery?->announcement;
        $student = $delivery?->user_id ? $announcement?->matchingStudents()->find($delivery->user_id) : null;
        if ($announcement === null || $student === null) {
            $this->complete(AnnouncementDelivery::SKIPPED);

            return;
        }

        try {
            $response = $telegram->sendAnnouncement($student, $announcement);
        } catch (ConnectionException) {
            $this->complete(AnnouncementDelivery::UNCONFIRMED);

            return;
        }

        $ok = $response->json('ok');
        if ($response->successful() && $ok === true) {
            $this->complete(AnnouncementDelivery::SENT, [
                'sent_at' => now(),
                'telegram_message_id' => $response->json('result.message_id'),
            ]);

            return;
        }

        $error = $response->json('error_code');
        $code = is_int($error) && $error >= 400 && $error <= 599 ? $error : $response->status();
        if ($code === 429 && $this->attempts() < $this->tries) {
            $delay = max(1, (int) $response->json('parameters.retry_after', 5));
            $retryAt = now()->addSeconds($delay);
            $this->complete(AnnouncementDelivery::PENDING, ['retry_at' => $retryAt]);
            $this->release($delay);

            return;
        }

        $status = $code >= 400 && $code < 500 && ($ok === false || $code === 429)
            ? AnnouncementDelivery::FAILED
            : AnnouncementDelivery::UNCONFIRMED;
        $this->complete($status, ['telegram_error_code' => $code]);
    }

    public function failed(): void
    {
        $this->complete(AnnouncementDelivery::UNCONFIRMED);
    }

    /** @param array<string, mixed> $details */
    private function complete(string $status, array $details = []): void
    {
        AnnouncementDelivery::query()
            ->whereKey($this->deliveryId)
            ->where('status', AnnouncementDelivery::SENDING)
            ->where('claim_token', $this->claimToken)
            ->update($details + ['status' => $status, 'claim_token' => null, 'retry_at' => null]);
    }
}
