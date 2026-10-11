<?php

namespace App\Domain\Cms\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Something other than a page (document, post, estate...) changed public output. */
class PublicContentChanged
{
    use Dispatchable;
}
