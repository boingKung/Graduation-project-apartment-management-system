<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Broadcast;

// อย่าลืมใส่ use 2 บรรทัดนี้ไว้ด้านบนของไฟล์
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Broadcast routes: authenticate tenant for broadcast channels
        Broadcast::routes(['middleware' => ['web', \App\Http\Middleware\AuthenticateTenantForBroadcast::class]]);

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('line', \SocialiteProviders\Line\Provider::class);
        });
        //
        Paginator::useBootstrapFive();
    }
}
