<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name',
    'degree_level',
    'university',
    'is_active',
])]
class Program extends Model
{
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class);
    }
}
