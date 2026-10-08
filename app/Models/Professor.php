<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $full_name
 * @property string|null $university_email
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'full_name',
    'university_email',
    'is_active',
])]
class Professor extends Model
{
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class);
    }
}
