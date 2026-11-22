<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $message
 * @property list<string> $languages
 * @property bool $without_program
 * @property int $minimum_submissions
 * @property Carbon|null $joined_before
 * @property bool $include_menu_button
 * @property Carbon|null $queued_at
 */
#[Fillable([
    'created_by',
    'title',
    'message',
    'languages',
    'without_program',
    'minimum_submissions',
    'joined_before',
    'include_menu_button',
    'queued_at',
])]
class Announcement extends Model
{
    public const LANGUAGES = ['fa' => 'Persian (Farsi)', 'it' => 'Italian', 'en' => 'English'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'without_program' => 'boolean',
            'minimum_submissions' => 'integer',
            'joined_before' => 'datetime',
            'include_menu_button' => 'boolean',
            'queued_at' => 'datetime',
        ];
    }

    /** @return HasMany<AnnouncementDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(AnnouncementDelivery::class);
    }

    /** @return Builder<User> */
    public function matchingStudents(): Builder
    {
        $query = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->where('telegram_user_id', '>', 0)
            ->where('is_blocked', false);

        if ($this->languages !== []) {
            $query->whereIn('preferred_language', $this->languages);
        }

        if ($this->without_program) {
            $query->whereNull('program_id');
        }

        if ($this->minimum_submissions > 0) {
            $query->whereHas('submittedResources', fn (Builder $resources) => $resources->has('files'), '>=', $this->minimum_submissions);
        }

        if ($this->joined_before !== null) {
            $query->where('created_at', '<', $this->joined_before);
        }

        return $query;
    }
}
