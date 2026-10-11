<?php

namespace App\Providers;

use App\Domain\Cms\Events\PageContentChanged;
use App\Domain\Cms\Events\PagePathChanged;
use App\Domain\Cms\Events\PublicContentChanged;
use App\Domain\Cms\MenuRepository;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Policies\DocumentPolicy;
use App\Domain\Settings\SettingsRepository;
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
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One instance per request so their in-memory copies are shared by every view composer call.
        $this->app->singleton(SettingsRepository::class);
        $this->app->singleton(MenuRepository::class);
    }

    public function boot(): void
    {
        if (! $this->app->runningUnitTests()) {
            // Only requests that actually send mail pay for loading the SMTP settings.
            $this->app->resolving('mail.manager', fn () => $this->app->make(MailConfigurator::class)->apply());
        }

        // The admin (AdminLTE) and public styles are Bootstrap-based; the default view is Tailwind and renders unstyled.
        Paginator::useBootstrapFive();

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Date::use(CarbonImmutable::class);

        Event::listen([Login::class, Logout::class, Failed::class], LogAuthActivity::class);

        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        RateLimiter::for('forms', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        View::composer(['layouts.site', 'site.*'], SiteComposer::class);

        Gate::policy(Document::class, DocumentPolicy::class);

        Event::listen(PagePathChanged::class, HandlePagePathChanged::class);
        Event::listen([PageContentChanged::class, PagePathChanged::class, PublicContentChanged::class], InvalidatePublicCaches::class);

        Password::defaults(fn () => Password::min(12)->mixedCase()->numbers()->uncompromised());
    }
}
