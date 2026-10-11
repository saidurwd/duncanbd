<?php

namespace App\Providers;

use App\Domain\Cms\Events\PageContentChanged;
use App\Domain\Cms\Events\PagePathChanged;
use App\Domain\Cms\Events\PublicContentChanged;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Policies\DocumentPolicy;
use App\Listeners\HandlePagePathChanged;
use App\Listeners\InvalidatePublicCaches;
use App\Listeners\LogAuthActivity;
use App\Support\MailConfigurator;
use App\View\Composers\SiteComposer;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->runningUnitTests()) {
            $this->app->booted(fn () => $this->app->make(MailConfigurator::class)->apply());
        }

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Date::use(CarbonImmutable::class);

        Event::listen([Login::class, Logout::class, Failed::class], LogAuthActivity::class);

        RateLimiter::for('forms', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        View::composer(['layouts.site', 'site.*'], SiteComposer::class);

        Gate::policy(Document::class, DocumentPolicy::class);

        Event::listen(PagePathChanged::class, HandlePagePathChanged::class);
        Event::listen([PageContentChanged::class, PagePathChanged::class, PublicContentChanged::class], InvalidatePublicCaches::class);

        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->uncompromised());
    }
}
