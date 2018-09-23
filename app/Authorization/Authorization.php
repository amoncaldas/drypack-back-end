<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */

namespace App\Authorization;

use App\Role;
use Carbon\Carbon;
use App\Authorization\Action;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use App\Authorization\AuthorizationSetup;


class Authorization
{
    /**
     * Get the resources that will be treated in the Dynamic Authorization.
     *
     * @param boolean $only_not_restricted with default false
     * @return array
     */
    public static function getResources($only_not_restricted = false) {
        // All resources declared in the config/authorization.php
        $all_resources = Config::get('authorization.resources');

        // the resources to be returned is initialized empty
        $selected_resources = [];

        // Closure to add resource to the array that will be returned
        $add = function($key, $value) use ($all_resources, &$selected_resources){
            $translation = \Lang::get("auth.resources.$key");
            $all_resources[$key]['name'] = $translation;
            $selected_resources[$key] = $all_resources[$key];
        };

        // If we should return all the resources
        if($only_not_restricted === false) {
            foreach($all_resources as $key => $value) {
                $add($key, $value);
            }
        } else { // if not, we check each resource if it is restricted
            $resources = collect($all_resources);

            $resources->each(function ($item, $key) use ($add) {
                if(!isset($item['restricted_to_logged_users']) || $item['restricted_to_logged_users'] === false) {
                    $add($key, $item);
                }
            });
        }

        return $selected_resources;
    }

    /**
     * Get the resources that will be treated in the Dynamic Authorization.
     *
     * @param string $slug
     * @return array|null
     */
    public static function getResource($slug) {
       $resources = Authorization::getResources();
       if(!empty($resources[$slug])) {
           return $resources[$slug];
       }
    }

    /**
     * Get the actions of a resource;
     *
     * @param string $slug
     * @return array|null
     */
    public static function getResourceActions($slug) {
        $resource = self::getResource($slug);

        $actions = [];

        foreach ($resource['actions'] as $key => $value) {
            if(is_array($value)) {
                $actions[] = $value["slug"];
            } else {
               $actions[] = $value;
            }
        }

        return $actions;
     }

    /**
     * Get the action types that will be treated in the Dynamic Authorization.
     *
     * @return array
     */
    public static function getActions() {
        $action_slugs =  Config::get('authorization.action_types');
        $actions_with_translations = [];
        foreach($action_slugs as $action_slug) {
            $translation = \Lang::get("auth.actions.$action_slug");
            $actions_with_translations[$action_slug] = ['name' => $translation];
        }
        return $actions_with_translations;
    }

    /**
     * Get the action types that will be treated in the Dynamic Authorization.
     *
     * @param string $slug
     * @return array|null
     */
    public static function getAction($slug) {
       $action_types = self::getActions();
       if(!empty($action_types[$slug])) {
           return $action_types[$slug];
       }
    }

    /**
     * Retrive a resource declared in config/authorization.php based in the resource controller name
     *
     * @param  string  $controller - full controller name, with namespace
     * @return Illuminate\Support\Collection $filtered collection of resources
     */
    public static function getResourceByController($controller) {

        // Here we get the collection of resource defined in /config/authorization.php
        // already mapped (resolving full controller namespace and slug key)
        $resources_collection = self::getMappedResourceCollection();

        // From the collection we extract the resource that has as controller the $controller
        $filtered =  $resources_collection->first(function ($value, $key) use ($controller) {
            return $value['controller'] === $controller;
        });

        return $filtered;

    }

    /**
     * Retrive the resources declared in config/authorization.php and make adjustments in the properties
     * like controller (to define the full namespace) and the slug
     *
     * @return Illuminate\Support\Collection  $mapped - Collection of mapped resources
     */
    protected static function getMappedResourceCollection() {

        // Retrive the resources declared in config/authorization.php
        $resources = self::getResources();

        // Set the default namespace for the controllers. It the resource does not specify a namespace, this one will be used
        $default_controller_namespace = "App\Http\Controllers\\";

        // Transforms the array of resources in a Illuminate\Support\Collection, easing the handling of it
        // The wildcard resource 'all' is excluded, because it is an abstract resource and can not be validated
        $collection = collect($resources)->filter(function ($value, $key) {
            return $key !== 'all' && isset($value['controller_class']);
        });

        // To turn it easier the resource filtering based in the controller, we define the controller full namespace
        // We also add the resource identifier 'slug' in the mapped object to make it easier filtering the collection
        $mapped =  $collection->map(function ($item, $key) use ($default_controller_namespace) {
            $item['controller'] = isset($item['namespace'])? $item['namespace']."\\".$item['controller_class'] : $default_controller_namespace.$item['controller_class'];
            $item['slug'] = $key;
            return $item;
        });

        return $mapped;
    }

    /**
     * Retrive the allowed actions for a given user
     *
     * @param  \App\User  $user - instance of user
     * @return array $actions - user allowed actions
     */
    public static function userAllowedActions($user)
    {
        $actions = [];
        $roles = $user->roles;
        foreach ($roles as $role) {
            $role_actions = is_array($role)? $role['actions']: $role->actions->toArray();
            $actions = array_merge($actions, $role_actions);
        }
        return $actions;
    }

    /**
     * Verifies whenever a controller should be verified or not
     *
     * @param string $controller
     * @param string $action_desired
     * @return false|Action
     */
    public static function verifiableController($controller){
        // Retrive the resource array in config/authorization.php by the controller name
        $verifiable_controller = Authorization::getResourceByController($controller);

        // If the resource does not exist and not listed in config/authorization.php we allow or not
        // based in the config value of allow_not_listed_controllers
        if(!isset($verifiable_controller)) {
           return false;
        }

        return $verifiable_controller;
    }

    /**
     * Verifies whenever a resource should be verified or not
     *
     * @param string $controller
     * @param string $action_desired
     * @return false|Action
     */
    public static function verifiableResource($resource_slug){
        // Retrive the resource array in config/authorization.php by the controller name
        $verifiable_resource = Authorization::getResource($resource_slug);

        // If the resource does not exist and not listed in config/authorization.php we allow or not
        // based in the config value of allow_not_listed_controllers
        if(!isset($verifiable_resource)) {
           return false;
        }

        $verifiable_resource["slug"] = $resource_slug;
        return $verifiable_resource;
    }


    /**
     * Verifies whenever an action should be verified or not
     *
     * @param array $resource
     * @param string $action_desired
     * @return false|Action
     */
    public static function verifiableAction(array $resource, $action_desired){

        // Retrive the Action instance based in the action slug passed
        $verifiable_action = Action::where('resource_slug',$resource['slug'])->where('action_type_slug', $action_desired)->first();

        // If the resource does not exist and not listed in config/authorization.php we allow or not
        // based in the config value of allow_not_listed_actions
        if(!isset($verifiable_action)){
            return false;
        }
        return $verifiable_action;
    }

    /**
     * Get the action or a boolean result if has the permission to execute an action
     *
     * @param string $controller
     * @param string $action_desired
     * @return boolean|Action
     */
    public static function actionOrResult($controller_or_slug, $action_desired, bool $byResourceSlug = null){
        if($byResourceSlug === true) {
            $verifiable_resource = self::verifiableResource($controller_or_slug);
        } else {
            $verifiable_resource = self::verifiableController($controller_or_slug);
        }
        if ($verifiable_resource === false) {
            return Config::get('authorization.allow_not_listed_controllers') === true;
        }

        $verifiable_action = self::verifiableAction($verifiable_resource, $action_desired);
        if ($verifiable_action === false) {
            return Config::get('authorization.allow_not_listed_actions') === true;
        }

        return $verifiable_action;
    }

    /**
     * Checks if a given user has the permission to run a given action (method) in a given resource (controller)
     * @param  string  $controller - full controller name, including namespace
     * @param  string  $action - action slug (method)
     * @param  \App\User|null  $user - instance of User. If not passed, we get the current logged user.
     * @return boolean - if the user has or not the permission to run the action
     */
    public static function hasPermission($controller, $action_desired, $user = null) {
        $action_or_result = self::actionOrResult($controller, $action_desired);
        if (is_bool($action_or_result)) {
            return $action_or_result;
        }

        // If a user is not passed, we get the current logged one
        $user = $user? $user: \Auth::user();

        // Get all user's role IDs
        $user_roles_ids = $user->roles()->get()->pluck('id')->all();

        // If we can find the resource and the action, we check in the DB if at least a role attached to the
        // user has the permission to run this action
        $count = \DB::table('role_actions')->whereIn('role_id', $user_roles_ids)->where('action_id',$action_or_result->id)->count();

        // return true or false based in the count
        return $count > 0;
    }

    /**
     * Checks if a given user has the permission to run a given action (method) in a given resource (controller)
     * @param  string  $controller - full controller name, including namespace
     * @param  string  $action - action slug (method)
     * @param  \App\User|null  $user - instance of User. If not passed, we get the current logged user.
     * @return boolean - if the user has or not the permission to run the action
     */
    public static function hasResourcePermission($resource_slug, $action_desired, $user = null) {
        $action_or_result = self::actionOrResult($resource_slug, $action_desired, true);
        if (is_bool($action_or_result)) {
            return $action_or_result;
        }

        // If a user is not passed, we get the current logged one
        $user = $user? $user: \Auth::user();

        // Get all user's role IDs
        $user_roles_ids = $user->roles()->get()->pluck('id')->all();

        // If we can find the resource and the action, we check in the DB if at least a role attached to the
        // user has the permission to run this action
        $count = \DB::table('role_actions')->whereIn('role_id', $user_roles_ids)->where('action_id',$action_or_result->id)->count();

        // return true or false based in the count
        return $count > 0;
    }

    /**
     * Check if a given Role has a action permission
     *
     * @param Role $role
     * @param string $controller
     * @param string $action_desired
     * @return boolean
     */
    public static function roleHasPermission(Role $role, $controller, $action_desired) {

        $action_or_result = self::actionOrResult($controller, $action_desired);
        if (is_bool($action_or_result)) {
            return $action_or_result;
        }

        $count = \DB::table('role_actions')->where('role_id', $role->id)->where('action_id',$action_or_result->id)->count();

        // return true or false based in the count
        return $count > 0;
    }

     /**
     * Build the denial message for the action not allowed based in the resource and action
     * identifiers and its name (defined in the Laravel translation)
     * @param  string  $controller - full controller name, including namespace
     * @param  string  $action - action slug
     * @return string - translated denial message
     */
    public static function getDenialMessage($controller, $action) {

        // Default message, in the case that we can not find the resource
        $msg = \Lang::get('auth.messages.no_authorization_for_this_resource');

        // Retrive the resource based in the full controller name, including namespace
        $resource = Authorization::getResourceByController($controller);

        // If we can not find the resource, we return the default message
        if(!$resource) {
            return $msg;
        }

        // Retrive the action by its slug
        $action = Authorization::getAction($action);

        // If we can not find the action, return a message only interpolating the resource name
        if(!$action) {
            $msg = \Lang::get('auth.messages.no_authorization_for_this_type_of_action_in_resource', ['resourceName' => $resource['name']]);
            return $msg;
        }

        // Build the message interpolating the resource an action name
        $msg = \Lang::get('auth.messages.no_authorization_for_action_in_resource', ['actionName'=>$action['name'],'resourceName' => $resource['name']]);

        return $msg;
    }

    /**
     * Get the minimu actions for role
     *
     * @param array $minimum_actions_filter eg.:["role", "password", "authentication:authenticate"];
     * @param Role $role
     * @return array with collection of arrays containing action
     */
    public static function getMergedActionsIDsUsingFilter($base_actions, array $additional_actions_filter){

        // Get the actions based in the filter(s)
        $additional_actions = AuthorizationSetup::getActionsByFilters($additional_actions_filter);

        // Get the IDs
        $actions_id = $base_actions->pluck('id')->all();
        $minimum_actions_id = $additional_actions->pluck('id')->all();

        // Get the diff and add to the final array
        $diffs = array_diff($minimum_actions_id, $actions_id);
        $all = array_merge($actions_id, $diffs);
        return $all;
    }
}
