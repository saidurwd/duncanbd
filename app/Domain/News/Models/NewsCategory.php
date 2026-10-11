<?php

namespace App\Domain\News\Models;

use App\Support\ClearsPublicCache;
use Illuminate\Database\Eloquent\Model;

class NewsCategory extends Model
{
    use ClearsPublicCache;

    protected $fillable = ['name', 'slug'];
}
