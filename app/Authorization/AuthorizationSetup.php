<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */

namespace App\Authorization;

use Illuminate\Support\Facades\Config;
use App\Authorization\Action;
use Illuminate\Support\Collection;
use App\Role;
use App\User;
use Carbon\Carbon;


class AuthorizationSetup
{
    /**
     * Remove all role actions relations, all actions and its dependencies.
     *
     * @return void
     */
    public static function removeActionsRoles() {
        \DB::table('role_actions')->delete();
        \DB::table('actions_dependencies')->delete();
        \DB::table('actions')->delete();
    }


    /**
     * Stores the possible actions for each resource in the DB
     *
     * @param array of resources
     * @return void
     */
    public static function storeResourcesActions($resources) {
        foreach ($resources as $res_key => $res_value) {
            // For each declared possible action for a resource
            foreach ($res_value['actions'] as $action) {
                // The action can be declared as a string or as an array (in the second case probably because it has dependencies)
                // To retrive the semantic identifier (slug), we do this verification
                $action_slug = is_array($action)?  $action['slug'] : $action;

                // The resource key is its slug, according what is expected to be declared in  config/authorization.php
                // So we create an possible action for a resource using the semantic identifier of each one
                $action_created = Action::create(['resource_slug' => $res_key,'action_type_slug'=>$action_slug]);

                // Here we store action dependencies
                self::storeActionDependencies($action, $action_created->id);
            }
        }
    }

    /**
     * Get actions by resource and action slug filters
     * It is possible to get action based in the following filters:
     *  1 - 'all' or '*' -> wildcard to get all the actions from all the resources
     *  2 - array containing string values with the slug of a resources from each all the actions should be got
     *  3 - array containing string values with the slug of a resource and the slug of an action, like 'user:store'
     *  4 - an array containing a mix of option 3 and 4
     * @param string|array resource(s)
     * @return collection of allowed actions
     */
    public static function getActionsByFilters($resources){
        // create an empty collection
        $allowed_actions = collect([]);

        // Here we treat the cases of wildcard that can means that we should get all the actions
        if(!is_array($resources) && ($resources === "*" || strtolower($resources) === "all")){
            $allowed_actions = Action::all();
        } else { // If is not wildcard and is a valid array, then we apply the filters

            // make sure it is an array, as a single filter can be passed
            $resources = is_array($resources)? $resources : [$resources];

            // Array of semantic identifiers of the resources.
            // If an action is not specified, then all the resource actions must be got
            $resource_slugs = [];

            // The array that will contain a collection of the pairs resource and action
            $resource_slugs_and_actions = [];

            foreach($resources as $resource) {
                // This case is when an action for a resource is specified
                if (\strpos($resource, ":") > 0){

                    $resource_data = explode(":", $resource);
                    $resource_slugs_and_actions[] = array("resource_slug"=> $resource_data[0], "action_type_slug"=> $resource_data[1]);

                } else { // This case is when only the resource is specified, so all the resource actions will be considered
                    $resource_slugs[] = $resource;
                }
            }

            // We get all the actions of each resource that has no action filter
            $allowed_actions = Action::whereIn('resource_slug', $resource_slugs)->get();

            // We add below the specified action for a resource
            foreach($resource_slugs_and_actions as $ra) {
                $action = Action::where('resource_slug', $ra['resource_slug'])->where('action_type_slug', $ra['action_type_slug'])->get();
                $allowed_actions->push($action->first());
            }
        }
        // all the actions, from resource only filter or "resource:action" filter are returned
        return $allowed_actions;
    }

    /**
     * Store the action dependencies
     * @param  array  $action containing action data
     * @param  int  $parent_action_id id of an action stored in the DB
     * @return void
     */
    protected static function storeActionDependencies($action, $parent_action_id){
        if(is_array($action) && isset($action['dependencies'])) {
            $dependencies = $action['dependencies'];
            foreach ($dependencies as $dependency) {
                // For each declared dependence, we retrive the dependent action from the DB
                $dependsOnAction = Action::where('resource_slug',$dependency['resource_slug'])
                    ->where('action_type_slug',$dependency['action_type_slug'])
                    ->first();

                // We insert the dependence relation in the DB
                \DB::table('actions_dependencies')->insert(
                    [
                        'dependent_action_id' => $parent_action_id,
                        'depends_on_action_id'=>$dependsOnAction->id
                    ]
                );
            }
        }
    }

    /**
     * Set the resources actions for a role
     *
     * @param array $resources
     * @param \App\Role $role
     * @return void
     */
    public static function setResourcesActionsForRole($resources, Role $role) {
        $allowed_actions = self::getActionsByFilters($resources);
        self::setActionsForRole($allowed_actions, $role);
    }

    /**
     * Set the resources actions for role identified by its slug
     *
     * @param array $resources
     * @param string $role_slug
     * @return \App\Role $role
     */
    public static function setResourcesActionsForRoleSlug($resources, $role_slug) {
        $role = Role::where('slug', $role_slug)->first();
        self::setResourcesActionsForRole($resources, $role);
        return $role;
    }


    /**
     * Set the allowed actions for a role
     *
     * @param Collection of \App\Authorization\Action $allowed_actions
     * @param \App\Role $role
     * @return void
     */
    public static function setActionsForRole(Collection $allowed_actions, Role $role) {
        // Role x actions relations that represent the allowed actions for a role that will be inserted in the DB
        $permissions = [];
        $now = Carbon::now();

        // For each action, an array with the role x action relation data (user id, action id and current timestamp) is created
        foreach ($allowed_actions as $action) {
            if($action->action_type_slug != 'all' && $action->resource_slug !== 'all') {
                $permissions[] = ['role_id'=>$role->id, 'action_id'=>$action->id, 'created_at'=>$now, 'updated_at'=>$now];
            }
        }
        // It is a full refresh, so we remove all the existing actions, and then set the passed actions
        \DB::table('role_actions')->where("role_id", $role->id)->delete();

        // The allowed actions are inserted in the DB
        \DB::table('role_actions')->insert($permissions);
    }

    /**
     * Define the actions for a role
     *
     * @param Collection of \App\Authorization\Action $allowed_actions
     * @param string $role_slug
     * @return \App\Role $role
     */
    public static function setActionsForRoleSlug(Collection $allowed_actions, $role_slug) {
        $role = Role::where('slug', $role_slug)->first();
        self::setActionsForRole($allowed_actions, $role);
        return $role;
    }

}
