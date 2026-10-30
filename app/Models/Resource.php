<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'course_id',
    'resource_type_id',
    'academic_year_id',
    'title',
    'description',
    'exam_date',
    'language',
    'has_solutions',
    'status',
    'submitted_by',
    'reviewed_by',
    'reviewed_at',
    'downloads_count',
    'inline_shares_count',
])]
class Resource extends Model
{
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        $flushSearchCaches = function () {
            Cache::forget('search:inline:en:default_latest');
            Cache::forget('search:inline:fa:default_latest');
            Cache::forget('search:inline:it:default_latest');
            Cache::forget('admin:dashboard:heavy_metrics');
        };

    
        static::deleted($flushSearchCaches);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function resourceType(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ResourceFile::class);
    }

    /**
     * Determine if this resource was submitted by a community student.
     */
    public function isCommunitySubmission(): bool
    {
        return $this->submitted_by !== null && $this->submitter !== null && $this->submitter->role !== 'admin';
    }

    /**
     * Determine if this resource is an official PARSI upload.
     */
    public function isOfficial(): bool
    {
        return ! $this->isCommunitySubmission();
    }

    /**
     * Build the standard Telegram caption for this resource, including course, title,
     * optional description, verified badge, extra content (e.g. download links), and official social media footer.
     */
    public function buildTelegramCaption(string $lang = 'en', ?string $titleSuffix = null, ?string $middleSection = null): string
    {
        $courseName = $this->course?->name ? e($this->course->name) : 'Course Material';
        $displayTitle = e($this->title) . ($titleSuffix ? " {$titleSuffix}" : '');

        $caption = null;
        if (! empty($this->description)) {
            $rawDesc = trim($this->description);
            $cleanDesc = e(mb_strlen($rawDesc) > 400 ? mb_substr($rawDesc, 0, 397) . '...' : $rawDesc);
            $caption .= "\n\n📝 <b>Description:</b>\n" . $cleanDesc;
        }
        $caption .= __('bot.resources.caption_header', [
            'course' => $courseName,
            'title' => $displayTitle,
        ], $lang);
        
        if ($middleSection !== null && $middleSection !== '') {
            $caption .= "\n\n" . trim($middleSection);
        }

        $channel = config('telegram.channel_username', 'PARSI_POLITO');
        $bot = config('telegram.bot_username', 'parsi_politobot');

        $caption .= __('bot.resources.caption_social_media', [
            'channel' => ltrim($channel, '@'),
            'bot' => ltrim($bot, '@'),
        ], $lang);

        return $caption;
    }
}
