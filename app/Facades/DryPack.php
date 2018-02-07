<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class DryPack extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'drypack';
    }
}
