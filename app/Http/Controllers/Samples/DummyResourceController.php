<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */
namespace App\Http\Controllers\Samples;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * This dummy resource controller is intended to be used to test the case
 * when an resource (represented by a controller) exist but is not declared on the
 * config/authorization.php
 */
class DummyResourceController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Dummy method
     */
    public function dummyMethod(Request $request)
    {
        return ['dummy_key' => 'dummy_value'];
    }
}
