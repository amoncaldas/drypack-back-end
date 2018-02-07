<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Faker\Generator as FakerGenerator;
use Faker\Factory as FakerFactory;
use Faker\Provider\pt_BR as FakerLanguageProvider;

class FakerServiceProvider extends ServiceProvider
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
        $this->app->singleton(FakerGenerator::class, function () {
            $faker = FakerFactory::create('pt_BR');

            $faker->addProvider(new FakerLanguageProvider\Person($faker));
            $faker->addProvider(new FakerLanguageProvider\Company($faker));
            $faker->addProvider(new FakerLanguageProvider\PhoneNumber($faker));
            $faker->addProvider(new FakerLanguageProvider\Address($faker));

            return $faker;
        });
    }
}
