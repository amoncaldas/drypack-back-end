<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */

namespace App\Http\Controllers;

use App\Role;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Database\Eloquent\Model;
use App\Authorization\Action;
use Carbon\Carbon;
use App\Util\DryPack;
use App\Authorization\Authorization;
use App\Exceptions\BusinessException;


class RolesController extends CrudController
{
    public function __construct()
    {
    }


    protected function getModel()
    {
        return Role::class;
    }

    protected function applyFilters(Request $request, $query){
        if ($request->includeAnonymous !== "true") {
            $query->where('slug', '<>', Role::anonymousRoleSlug());
        }
    }

    /**
     * Return the role validation rule
     *
     * @param Request $request
     * @param Model $obj
     * @return void
     */
    protected function getValidationRules(Request $request, Model $obj)
    {
        // append the id in the unique title validation to avoid failing in the update validation
        $uniqueFilterAppend = isset($obj->id)?  ','.$obj->id : '';

        // The role title is required and must be unique
        return ['title'=>'required|unique:roles,title'.$uniqueFilterAppend];
    }


    /**
     * Automatically define the role slug from its name before saving the role
     *
     * @param Request $request
     * @param Model $role
     * @return void
     */
    protected function beforeStore(Request $request, Model $role) {
        if(!isset($role->slug)) {
            $role->slug = DryPack::getSlug($role->title);
        }
    }

    /**
     * Define the minimum permissions for admin role before update
     *
     * @param Request $request
     * @param Model $role
     * @return void
     */
    protected function beforeUpdate(Request $request, Model $role)
    {
        // The admin role must have at least the permissions to authenticate,
        // reset password and manage roles/permissions
        if($role->slug == Role::defaultAdminRoleSlug()) {
            $actions = $role->actions()->get();
            $all = Authorization::getMergedActionsIDsUsingFilter($actions, ["role", "password", "authentication:authenticate"]);
            if(count($all) > $actions->count()) {
                $mapped = [];
                foreach ($all as $action_id) {
                    $mapped[] = ["id"=> $action_id];
                }
                // We add the modified/mapped header to the request, so it will be consumed in the update
                $request->merge(["actions" => $mapped, "warning"=>'mandatory_permissions_added']);
            }
        }
    }

    /**
     * Before delete check if the role can be removed
     *
     * @param Request $request
     * @param Model $obj
     * @return void
     */
    protected function beforeDestroy(Request $request, Model $role) {
        if(!$role->isRemovable()) {
            throw new BusinessException('role_can_not_be_removed');
        }
        else {
            // Remove all actions related before remove the role
            // Sync with a empty array will delete all from the relation
            $role->actions()->sync([]);
        }
    }

    /**
     * Save role actions permissions using the relationship
     *
     * @param Request $request
     * @param Model $role
     * @return void
     */
    protected function afterSave(Request $request, Model $role) {
        if($request->has('actions')){
            // Get the new list slugs of thew old actions permissions
            $old_permissions = $role->actions()->get()->pluck('action_type_slug', 'resource_slug')->all();

            // Remove the existing actions permissions for this role and then
            // add the actions from request
            // Sync with a empty array will delete all
            $role->actions()->sync([]);

            // Save the new actions permissions
            $this->saveRoleActions($role, $request->actions);

            // Get the new list slugs of thew new actions permissions saved
            $new_permissions = $role->actions()->get()->pluck('action_type_slug', 'resource_slug')->all();

            // Store an audit describing the role actions permissions changed
            $role->storeAudit('updated','permissions', $old_permissions, $new_permissions);
        }
    }

    /**
     * Save the actions associated to a role
     *
     * @param Model $role
     * @param array $actions
     */
    private function saveRoleActions(Model $role, array $actions)  {
        $now = Carbon::now();
        foreach ($actions as $a) {
            // Check if the action array has the action id we need
            if(!empty($a['id'])){

                // retrive the action from the DB by its ID
                $action = Action::find($a['id']);

                // Avoid storing wildcard actions ad resources 'all'
                if(isset($action) && $action['action_type_slug'] != 'all' && $action['resource_slug'] != 'all'){

                    // Save the action using relationship
                    $role->actions()->save($action, ['created_at'=>$now, 'updated_at'=>$now]);
                }
            }
        }
    }


}
