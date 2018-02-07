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
 * This dummy authorization controller is intended to be used to test the case
 * when an resource a resource is listed in the config/authorization.php file but one of its actions (represented by a method)
 * exist but is not listed
 */
class DummyActionController extends Controller
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
