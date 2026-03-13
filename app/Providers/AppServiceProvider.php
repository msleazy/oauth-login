<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->app['events']->listen(
            SocialiteWasCalled::class,
            function (SocialiteWasCalled $event) {
                $event->extendSocialite('discord', \SocialiteProviders\Discord\Provider::class);
                $event->extendSocialite('spotify', \SocialiteProviders\Spotify\Provider::class);
            }
        );
    }
}