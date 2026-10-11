<?php

namespace App\Domain\Careers\Models;

use App\Domain\Careers\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Application extends Model
{
    use LogsActivity;

    protected $fillable = ['vacancy_id', 'name', 'email', 'phone', 'cover_letter', 'cv_path', 'cv_original_name', 'cv_mime', 'cv_size', 'status', 'internal_note', 'consent_at', 'ip'];

    /** @var array<string, string> */
    protected $casts = ['status' => ApplicationStatus::class, 'consent_at' => 'datetime'];

    /** @return BelongsTo<Vacancy, $this> */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class)->withTrashed();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status'])->logOnlyDirty()->useLogName('careers');
    }
}
