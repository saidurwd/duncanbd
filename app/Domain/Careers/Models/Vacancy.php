<?php

namespace App\Domain\Careers\Models;

use App\Domain\Careers\Enums\EmploymentType;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Support\ClearsPublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Vacancy extends Model
{
    use ClearsPublicCache, LogsActivity, SoftDeletes;

    protected $fillable = ['title', 'slug', 'department', 'location', 'employment_type', 'description', 'requirements', 'closing_date', 'status', 'published_at', 'closed_at'];

    /** @var array<string, string> */
    protected $casts = [
        'employment_type' => EmploymentType::class,
        'status' => DocumentStatus::class,
        'closing_date' => 'date',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Open for applications: published, started, and not past its closing date (the whole closing day counts).
     *
     * @param  Builder<Vacancy>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', DocumentStatus::Published->value)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('closing_date')->orWhere('closing_date', '>=', now('Asia/Dhaka')->toDateString()));
    }

    public function isOpen(): bool
    {
        return $this->status === DocumentStatus::Published
            && ($this->published_at === null || $this->published_at->isPast())
            && ($this->closing_date === null || $this->closing_date->toDateString() >= now('Asia/Dhaka')->toDateString());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['title', 'status', 'closing_date'])->logOnlyDirty()->useLogName('careers');
    }
}
