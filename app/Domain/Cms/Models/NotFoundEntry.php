<?php

namespace App\Domain\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class NotFoundEntry extends Model
{
    protected $table = 'not_found_log';

    public $timestamps = false;

    protected $fillable = ['path', 'referrer', 'hits', 'last_seen_at'];

    /** @var array<string, string> */
    protected $casts = ['last_seen_at' => 'datetime'];
}
