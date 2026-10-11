<?php

namespace App\Domain\Cms\Events;

use App\Domain\Cms\Models\Page;
use Illuminate\Foundation\Events\Dispatchable;

class PagePathChanged
{
    use Dispatchable;

    public function __construct(public Page $page, public string $oldPath) {}
}
