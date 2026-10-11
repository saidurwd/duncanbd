<?php

namespace App\Domain\Engagement\Models;

use App\Domain\Engagement\Enums\EnquiryCategory;
use App\Domain\Engagement\Enums\EnquiryStatus;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Enquiry extends Model
{
    use LogsActivity;

    protected $fillable = ['category', 'name', 'email', 'phone', 'organisation', 'message', 'status', 'assigned_to', 'internal_note', 'ip', 'user_agent', 'consent_at'];

    /** @var array<string, string> */
    protected $casts = ['category' => EnquiryCategory::class, 'status' => EnquiryStatus::class, 'consent_at' => 'datetime'];

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function getActivitylogOptions(): LogOptions
    {
        // Personal data (name, email, message) is deliberately kept out of the audit log.
        return LogOptions::defaults()->logOnly(['status', 'assigned_to'])->logOnlyDirty()->useLogName('enquiries');
    }
}
