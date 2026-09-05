<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $telegram_user_id
 * @property string|null $username
 * @property string $first_name
 * @property string|null $last_name
 * @property string $preferred_language
 * @property string $role
 * @property bool $is_blocked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'telegram_user_id',
    'username',
    'first_name',
    'last_name',
    'preferred_language',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'telegram_user_id' => 'integer',
            'is_blocked' => 'boolean',
        ];
    }

    public function submittedResources(): HasMany
    {
        return $this->hasMany(Resource::class, 'submitted_by');
    }

    public function reviewedResources(): HasMany
    {
        return $this->hasMany(Resource::class, 'reviewed_by');
    }

}