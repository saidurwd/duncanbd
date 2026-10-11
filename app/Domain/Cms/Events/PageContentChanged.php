<?php

namespace App\Domain\Cms\Events;

use App\Domain\Cms\Models\Page;
use Illuminate\Foundation\Events\Dispatchable;

/** Fired whenever a page's public output may have changed (save, publish, unpublish). */
class PageContentChanged
{
    use Dispatchable;

    public function __construct(public Page $page) {}
}
