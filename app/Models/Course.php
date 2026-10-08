<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}
