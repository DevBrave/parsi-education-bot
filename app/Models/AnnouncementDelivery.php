<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 */
#[Fillable(['status', 'claim_token', 'retry_at', 'sent_at', 'telegram_message_id', 'telegram_error_code'])]
class AnnouncementDelivery extends Model
{
    public const PENDING = 'pending';

    public const SENDING = 'sending';

    public const SENT = 'sent';

    public const SKIPPED = 'skipped';

    public const FAILED = 'failed';

    public const UNCONFIRMED = 'unconfirmed';

    public const STATUSES = [
        self::PENDING => 'Pending',
        self::SENDING => 'Sending',
        self::SENT => 'Sent',
        self::SKIPPED => 'Skipped',
        self::FAILED => 'Failed',
        self::UNCONFIRMED => 'Unconfirmed',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['retry_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    /** @return BelongsTo<Announcement, $this> */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
