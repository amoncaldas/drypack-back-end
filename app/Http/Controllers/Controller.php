<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Traits\Validation;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, Validation;

    protected $perPage = 10;
}
