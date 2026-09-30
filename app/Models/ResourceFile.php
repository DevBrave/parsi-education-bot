<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $resource_id
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $sha256
 * @property string|null $drive_file_id
 * @property string|null $telegram_file_id
 * @property string|null $telegram_file_unique_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
#[Fillable([
    'resource_id',
    'original_filename',
    'mime_type',
    'size_bytes',
    'sha256',
    'drive_file_id',
    'telegram_file_id',
    'telegram_file_unique_id',
])]
class ResourceFile extends Model
{
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
