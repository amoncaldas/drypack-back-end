<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Config;
trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // All te mail sending is redirected to the log during tests
        $app->make('config')->set('mail.driver', 'log');


        $this->adminUserData = factory(\App\User::class)->states('admin-plain-password')->make()->getAttributes();
        $this->basicUserData = factory(\App\User::class)->states('basic-plain-password')->make()->getAttributes();

        return $app;
    }

    /**
     * Set up the application
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        \Artisan::call('migrate:reset', []);
        \Artisan::call('migrate', ['--seed' => true]);

        // We just enable the audit in console mode after the seed,
        //  so during the tests also run the audit code
        $this->app->make('config')->set('audit.console', true);
    }
}

