<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'name',
    'course_code',
    'credits',
    'year',
    'is_active',
])]
class Course extends Model
{
    protected static function booted(): void
    {
        static::saved(function (Course $course) {
            $course->clearCourseCaches();
        });

        static::deleted(function (Course $course) {
            $course->clearCourseCaches();
        });
    }

    /**
     * Invalidate cached catalog entries for all programs associated with this course.
     */
    public function clearCourseCaches(): void
    {
        $programs = $this->programs()->with('tracks')->get();
        foreach ($programs as $program) {
            Cache::forget("catalog:years:program_{$program->id}");
            Cache::forget("catalog:courses:program_{$program->id}:year_all");
            Cache::forget("catalog:courses:program_{$program->id}:track_all:year_all");
            for ($y = 1; $y <= 5; $y++) {
                Cache::forget("catalog:courses:program_{$program->id}:year_{$y}");
                Cache::forget("catalog:courses:program_{$program->id}:track_all:year_{$y}");
            }
            foreach ($program->tracks as $t) {
                Cache::forget("catalog:courses:program_{$program->id}:track_{$t->id}:year_all");
                for ($y = 1; $y <= 5; $y++) {
                    Cache::forget("catalog:courses:program_{$program->id}:track_{$t->id}:year_{$y}");
                }
            }
        }
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credits' => 'float',
            'year' => 'integer',
        ];
    }

    /**
     * Get human-readable year label (e.g. 1st Year, 2nd Year, 3rd Year).
     */
    public function getYearLabelAttribute(): string
    {
        return match ($this->year) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
            5 => '5th Year',
            default => $this->year ? "Year {$this->year}" : 'General / Elective',
        };
    }
}
