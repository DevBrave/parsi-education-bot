<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property int $telegram_user_id
 * @property string|null $username
 * @property string $first_name
 * @property string|null $last_name
 * @property string $preferred_language
 * @property string $role
 * @property int|null $program_id
 * @property bool $is_blocked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'telegram_user_id',
    'username',
    'email',
    'password',
    'first_name',
    'last_name',
    'preferred_language',
    'program_id',
    'track_id',
    'role',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'telegram_user_id' => 'integer',
            'program_id' => 'integer',
            'track_id' => 'integer',
            'is_blocked' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->telegram_user_id) {
                Cache::forget("user_id:telegram_{$user->telegram_user_id}");
                Cache::forget("user:telegram_{$user->telegram_user_id}");
            }
        });

        static::deleted(function (User $user) {
            if ($user->telegram_user_id) {
                Cache::forget("user_id:telegram_{$user->telegram_user_id}");
                Cache::forget("user:telegram_{$user->telegram_user_id}");
            }
        });
    }

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MODERATOR = 'moderator';
    public const ROLE_MAJOR_ADMIN = 'major_admin';
    public const ROLE_PROFESSOR = 'professor';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_VIEWER = 'viewer';
    public const ROLE_STUDENT = 'student';
    public const ROLE_VIP = "vip"; // TODO : ADD nishit and ilia as vip

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function submittedResources(): HasMany
    {
        return $this->hasMany(Resource::class, 'submitted_by');
    }

    public function reviewedResources(): HasMany
    {
        return $this->hasMany(Resource::class, 'reviewed_by');
    }

    /**
     * Role checks.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isModerator(): bool
    {
        return $this->role === self::ROLE_MODERATOR;
    }

    public function isMajorAdmin(): bool
    {
        return $this->role === self::ROLE_MAJOR_ADMIN || $this->role === self::ROLE_PROFESSOR;
    }

    public function isEditor(): bool
    {
        return $this->role === self::ROLE_EDITOR;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    public function isProfessor(): bool
    {
        return $this->role === self::ROLE_PROFESSOR || $this->role === self::ROLE_MAJOR_ADMIN;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT || ! $this->isStaff();
    }

    /**
     * Staff access check (all roles that can log into the web management panel).
     */
    public function isStaff(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_MODERATOR,
            self::ROLE_MAJOR_ADMIN,
            self::ROLE_PROFESSOR,
            self::ROLE_EDITOR,
            self::ROLE_VIEWER,
        ], true);
    }

    /**
     * Permission checks.
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canManageCatalog(): bool
    {
        return $this->isAdmin() || $this->isEditor() || $this->isModerator(); 
    }

    public function canViewReports(): bool
    {
        return $this->isStaff();
    }

    public function canViewLogs(): bool
    {
        return $this->isAdmin() || $this->isModerator();
    }

    public function canManageReport(ResourceReport $report): bool
    {
        if ($this->isAdmin() || $this->isModerator() || $this->isEditor()) {
            return true;
        }

        if ($this->isProfessor()) {
            return $report->resource ? $this->isResourceInMyMajor($report->resource) : false;
        }

        return false;
    }

    public function canCreateResources(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_MODERATOR,
            self::ROLE_MAJOR_ADMIN,
            self::ROLE_PROFESSOR,
            self::ROLE_EDITOR,
        ], true);
    }

    public function canEditResource(Resource $resource): bool
    {
        if ($this->isAdmin() || $this->isModerator() || $this->isEditor()) {
            return true;
        }

        if ($this->isProfessor()) {
            return $this->isResourceInMyMajor($resource);
        }

        return false;
    }

    public function canDeleteResource(Resource $resource): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isProfessor()) {
            return $this->isResourceInMyMajor($resource);
        }

        return false;
    }

    public function canApproveResource(Resource $resource): bool
    {
        if ($this->isAdmin() || $this->isModerator() || $this->isEditor()) {
            return true;
        }

        if ($this->isProfessor()) {
            return $this->isResourceInMyMajor($resource);
        }

        return false;
    }

    public function canMutateResource(Resource $resource): bool
    {
        return $this->canEditResource($resource) || $this->canDeleteResource($resource);
    }

    public function isResourceInMyMajor(Resource $resource): bool
    {
        if (! $this->program_id) {
            return false;
        }

        $course = $resource->relationLoaded('course') ? $resource->course : $resource->course()->with('programs')->first();
        if (! $course) {
            return false;
        }

        return $course->programs->contains('id', $this->program_id);
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Super Admin',
            self::ROLE_MODERATOR => 'Moderator',
            self::ROLE_MAJOR_ADMIN => 'Major Admin' . ($this->program ? " ({$this->program->name})" : ''),
            self::ROLE_PROFESSOR => 'Professor' . ($this->program ? " ({$this->program->name})" : ''),
            self::ROLE_EDITOR => 'Editor',
            self::ROLE_VIEWER => 'Viewer (Read-only)',
            default => 'Student',
        };
    }
}
