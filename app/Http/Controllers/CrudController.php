<?php
/**
 * CrudController is a shared base controller that provides a CRUD basis for Laravel applications.
 *
 * @author Jamie Rumbelow <jamie@jamierumbelow.net>
 * @license http://opensource.org/licenses/MIT
 */

namespace App\Http\Controllers;

use JWTAuth;
use App\BaseModel;
use App\Util\DryPack;
use App\Http\Requests;
use Illuminate\Http\Request;
use App\Http\Traits\Actions;
use App\Http\Traits\Callbacks;
use App\Authorization\Authorization;
use App\Http\Controllers\Controller;
use \App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentQueryBuilder;


abstract class CrudController extends Controller
{
    use Callbacks, Actions;

    /**
     * Get the model class.
     *
     * @return Model
     */
    abstract protected function getModel();

    /**
     * Get validation rules.
     * @param Request $request
     * @param Model
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
        $isFromAdminUrl = ($origin === "$root/admin");
        $result = $isFromAdminUrl && !$this->isExternalRequest();
        return $result;
    }

    /**
     * Verifies if the request was made from the admin url
     *
     * @return boolean
     */
    protected function isExternalRequest() {
        $origin = request()->header("referer");
        $appUrl = env('APP_URL');

        // in the case we arerunning the app locally, we need to treat the special case of localhost/0.0.0.0
        if (DryPack::contains($appUrl, "localhost") && DryPack::contains($origin, "0.0.0.0")) {
            $appUrl = str_replace("localhost", "0.0.0.0", $appUrl);
        }
        if (DryPack::contains($appUrl, "0.0.0.0") && DryPack::contains($origin, "localhost")) {
            $appUrl = str_replace("0.0.0.0", "localhost", $appUrl);
        }
        $startsWith = DryPack::startsWith($origin, $appUrl);

        return !$startsWith;
    }

    /**
     * Apply index only user's content if s/he doesn't have the index_others permission
     *
     * @param EloquentQueryBuilder $query
     * @param string $ownerIdField, default "owner_id"
     * @return void
     */
    protected function applyIndexOthersPermissionFilter (EloquentQueryBuilder $query, $ownerIdField = "owner_id") {
        // if the user has not the permission to index other users' content
        // and the content has this type of action as an assignable permission
        // limit the contents to the ones that belongs to the current user
        $user = $this->getUser();
        $resourceActions = Authorization::getResourceActions("media");
        if(isset($resourceActions["index_others"]) && $user->hasResourcePermission("media", "index_others")) {
            $query->where($ownerIdField, $this->getUser()->id);
        }
    }

     /**
     * Check if the curent user can destroy/delete the media. If not, raise a BusinessException
     *
     * @param Model $model
     * @param String $resourceName
     * @param String $ownerIdField, default "owner_id"
     * @throws BusinessException
     * @return void
     */
    protected function checkDestroyOthersPermission(Model $model, $resourceName, $ownerIdField = "owner_id") {
        $user = $this->getUser();
        $resourceActions = Authorization::getResourceActions($resourceName);

        if($model->$ownerIdField !== $user->id && isset($resourceActions["destroy_others"]) && !$user->hasResourcePermission($resourceName, "destroy_others")) {
            throw new BusinessException("you_dont_have_permission_to_destroy_this_content");
        }
    }

    /**
     * Check if the curent user can update the content. If not, raise a BusinessException
     *
     * @param Model $model
     * @param String $resourceName
     * @param String $ownerIdField, default "owner_id"
     * @throws BusinessException
     * @return void
     */
    protected function checkUpdateOthersPermission(Model $model, $resourceName, $ownerIdField = "owner_id") {
        $user = $this->getUser();
        $resourceActions = Authorization::getResourceActions($resourceName);

        if($model->$ownerIdField !== $user->id && isset($resourceActions["update_others"]) && !$user->hasResourcePermission($resourceName, "update_others")) {
            throw new BusinessException("you_dont_have_permission_to_update_this_content");
        }
    }

    /**
     * Check if the curent user can update the content owner. If not, raise a BusinessException
     *
     * @param Request $request
     * @param String $resourceName
     * @param String $ownerIdField, default "owner_id"
     * @throws BusinessException
     * @return void
     */
    protected function checkUpdateOwnerPermission(Request $request, $resourceName, $ownerIdField = "owner_id") {
        $user = $this->getUser();
        $resourceActions = Authorization::getResourceActions($resourceName);

        if($request->has($ownerIdField)) {
            // if the update owner is not monitored or it is monitored and the current user has this permission, update it
            if (!isset($resourceActions["update_owner"]) && !$user->hasResourcePermission($resourceName, "update_owner") && $request->$ownerIdField !== $user->id) {
                throw new BusinessException("you_dont_have_permission_to_change_the_owner_of_this_item");
            }
        }
    }
}
