<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $resource_id
 * @property int|null $resource_file_id
 * @property int|null $user_id
 * @property string $reason
 * @property string|null $details
 * @property 'pending'|'resolved'|'dismissed' $status
 * @property string|null $admin_notes
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'resource_id',
    'resource_file_id',
    'user_id',
    'reason',
    'details',
    'status',
    'admin_notes',
    'resolved_by',
    'resolved_at',
])]
class ResourceReport extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    public const REASON_CORRUPTED = 'corrupted';
    public const REASON_WRONG_COURSE = 'wrong_course';
    public const REASON_POOR_QUALITY = 'poor_quality';
    public const REASON_COPYRIGHT = 'copyright';
    public const REASON_OTHER = 'other';

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function resourceFile(): BelongsTo
    {
        return $this->belongsTo(ResourceFile::class);
    }

    /**
     * Query Scopes
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeDismissed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DISMISSED);
    }

    public function scopeForUser(Builder $query, ?User $user): Builder
    {
        if ($user && $user->isProfessor() && $user->program_id) {
            $programId = $user->program_id;
            return $query->whereHas('resource.course.programs', function ($q) use ($programId) {
                $q->where('programs.id', $programId);
            });
        }

        return $query;
    }

    /**
     * Get human readable reason label.
     */
    public function getReasonLabelAttribute(): string
    {
        return match ($this->reason) {
            self::REASON_CORRUPTED => '❌ Corrupted / Unreadable File',
            self::REASON_WRONG_COURSE => '⚠️ Wrong Subject or Course',
            self::REASON_POOR_QUALITY => '📉 Poor Quality / Outdated',
            self::REASON_COPYRIGHT => '⚖️ Copyright or Privacy Concern',
            default => '📝 Other Issue',
        };
    }
}
