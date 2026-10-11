<?php

namespace App\Domain\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = ['from_path', 'to_url', 'status_code', 'is_automatic', 'hits', 'last_hit_at'];

    /** @var array<string, string> */
    protected $casts = ['is_automatic' => 'boolean', 'last_hit_at' => 'datetime'];
}
