<?php

namespace Tests;

use Exception;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use CreatesApplication, TokenHelper, AssertsHelper;

    protected $apiPath = '/api/v1';
    protected $webPath = '';
    protected $faker;
    protected $adminUserData;
    protected $basicUserData;

    public function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }
}
