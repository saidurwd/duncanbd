<?php

namespace App\Listeners;

use App\Domain\Cms\Events\PagePathChanged;
use App\Domain\Cms\RedirectManager;

class HandlePagePathChanged
{
    public function __construct(private RedirectManager $redirects) {}

    public function handle(PagePathChanged $event): void
    {
        $this->redirects->pageMoved($event->page, $event->oldPath);
    }
}
