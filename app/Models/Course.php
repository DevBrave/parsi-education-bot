<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'course_code',
    'credits',
    'is_active',
])]
class Course extends Model
{
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
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
        ];
    }
}
