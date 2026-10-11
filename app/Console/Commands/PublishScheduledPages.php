<?php

namespace App\Console\Commands;

use App\Domain\Cms\Enums\PageStatus;
use App\Domain\Cms\Events\PageContentChanged;
use App\Domain\Cms\Models\Page;
use Illuminate\Console\Command;

class PublishScheduledPages extends Command
{
    protected $signature = 'pages:publish-scheduled';

    protected $description = 'Publish pages whose scheduled time has arrived and unpublish those that have expired';

    public function handle(): int
    {
        $due = Page::where('status', PageStatus::Scheduled->value)->where('published_at', '<=', now())->get();
        foreach ($due as $page) {
            $page->update(['status' => PageStatus::Published]);
            PageContentChanged::dispatch($page);
        }

        $expired = Page::where('status', PageStatus::Published->value)->whereNotNull('unpublish_at')->where('unpublish_at', '<=', now())->get();
        foreach ($expired as $page) {
            $page->update(['status' => PageStatus::Archived]);
            PageContentChanged::dispatch($page);
        }

        $this->info("Published {$due->count()}, archived {$expired->count()}.");

        return self::SUCCESS;
    }
}
