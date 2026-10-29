<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'program_id',
    'name',
    'code',
    'description',
    'is_active',
])]
class Track extends Model
{
    protected static function booted(): void
    {
        static::saved(function (Track $track) {
            Cache::forget("catalog:tracks:program_{$track->program_id}");
        });

        static::deleted(function (Track $track) {
            Cache::forget("catalog:tracks:program_{$track->program_id}");
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
