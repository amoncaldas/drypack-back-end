<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $locale = config('app.locale');

        // Define the locale to carbon and php directly using default app locale
        setlocale(LC_ALL, $locale, $locale . '.utf-8', $locale . '.utf-8');
        \Carbon::setLocale(config('app.locale'));

        App::bind('drypack', function () {
            return new \App\Util\DryPack;
        });

        // if the application is running in a non production environment
        // redirect the emails to the log file
        $env = getenv('APP_ENV');
        if($env === "development" || $env === "local"){
            App::make('config')->set('mail.driver', 'log');
        }
    }
}
