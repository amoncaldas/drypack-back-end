<?php
/**
 * CrudController is a shared base controller that provides a CRUD basis for Laravel applications.
 *
 * @author Jamie Rumbelow <jamie@jamierumbelow.net>
 * @license http://opensource.org/licenses/MIT
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Traits\Callbacks;
use App\Http\Traits\Actions;
use JWTAuth;
use App\Util\DryPack;

abstract class CrudController extends Controller
{
    use Callbacks, Actions;

    /**
     * Get the model class.
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    abstract protected function getModel();

    /**
     * Get validation rules.
     * @param \Illuminate\Http\Request $request
     * @param Illuminate\Database\Eloquent\Model
     * @return void
     */
    abstract protected function getValidationRules(Request $request, Model $obj);

    /**
     * Check whenever there is a authenticated user
     *
     * @return boolean
     */
    protected function isAuthenticated() {
        $user = $this->getUser();
        return isset($user);
    }

    /**
     * Get the current logged user
     *
     * @return void
     */
    protected function getUser() {
        $user = JWTAuth::parseToken()->authenticate();
        return $user;
    }

    /**
     * Verifies if the request was made from the admin url
     *
     * @return boolean
     */
    protected function isAdmin() {
        $origin = request()->header("referer");
        $root = request()->root();
        $result = $origin === "$root/admin" && !$this->isExternalRequest();
        return $result;
    }

    /**
     * Verifies if the request was made from the admin url
     *
     * @return boolean
     */
    protected function isExternalRequest() {
        $origin = request()->header("referer");
        $result = DryPack::startsWith($origin, env('APP_URL'));
        return $result;
    }
}
