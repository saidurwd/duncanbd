<?php

namespace App\Domain\Engagement\Models;

use App\Domain\Engagement\Enums\SubscriberStatus;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    protected $fillable = ['email', 'status', 'consent_at', 'confirmed_at', 'unsubscribed_at', 'ip'];

    /** @var array<string, string> */
    protected $casts = ['status' => SubscriberStatus::class, 'consent_at' => 'datetime', 'confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
}
