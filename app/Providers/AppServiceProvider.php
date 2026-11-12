<?php

namespace App\Providers;

use App\Services\Cart;
use App\Services\Wishlist;
use App\Support\Seo;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Seo::class);
        $this->app->scoped(Cart::class);
        $this->app->scoped(Wishlist::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Paginator::defaultView('partials.pagination');

        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Event::listen(Login::class, function (Login $event) {
            if ($event->guard !== 'web') {
                return;
            }
            app(Wishlist::class)->mergeInto($event->user);
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        });
    }
}
