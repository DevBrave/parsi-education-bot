<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'name',
    'degree_level',
    'university',
    'is_active',
])]
class Program extends Model
{
    protected static function booted(): void
    {
        static::saved(function ($program) {
            Cache::forget('catalog:programs:active');
            Cache::forget('catalog:degrees:unique_levels');
            if ($program->degree_level) {
                Cache::forget("catalog:programs:degree_{$program->degree_level}");
            }
        });

        static::deleted(function ($program) {
            Cache::forget('catalog:programs:active');
            Cache::forget('catalog:degrees:unique_levels');
            if ($program->degree_level) {
                Cache::forget("catalog:programs:degree_{$program->degree_level}");
            }
        });
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function defaultYears(): array
    {
        $level = strtolower($this->degree_level ?? '');

        if (in_array($level, ['msc', 'master', 'laurea magistrale'])) {
            return [1, 2];
        }

        return [1, 2, 3];
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
