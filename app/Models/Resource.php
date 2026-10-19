<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'course_id',
    'resource_type_id',
    'academic_year_id',
    'title',
    'description',
    'exam_date',
    'language',
    'has_solutions',
    'status',
    'submitted_by',
    'reviewed_by',
    'reviewed_at',
    'downloads_count',
    'inline_shares_count',
])]
class Resource extends Model
{
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function resourceType(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ResourceFile::class);
    }

    /**
     * Determine if this resource was submitted by a community student.
     */
    public function isCommunitySubmission(): bool
    {
        return $this->submitted_by !== null && $this->submitter !== null && $this->submitter->role !== 'admin';
    }

    /**
     * Determine if this resource is an official PARSI upload.
     */
    public function isOfficial(): bool
    {
        return ! $this->isCommunitySubmission();
    }
}
