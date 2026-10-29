<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $code
 * @property string $display_name
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable([
    'code',
    'display_name',
    'sort_order',
    'is_active',
])]
class ResourceType extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::saved(function () {
            // Cache::forget('catalog:resource_types:active');
            Cache::forget('catalog:resource_types:active_array');
        });

        static::deleted(function () {
            // Cache::forget('catalog:resource_types:active');
            Cache::forget('catalog:resource_types:active_array');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
