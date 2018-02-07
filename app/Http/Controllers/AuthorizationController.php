<?php

namespace App\Http\Controllers;

use Log;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\CrudController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Traits\GenericService;
use App\Authorization\Authorization;
use App\Authorization\Action;
use App\Role;



class AuthorizationController extends Controller
{
    use GenericService;


    public function __construct()
    {
         /**
         * This tells the GenericService to do not validate the filters
         *
         * @var array
         */
        $this->gsSkipFiltersValidation = true;
    }

    /**
    * Gets the classes
    * @param request inject the request data
    * @return array resources
    **/
    public function resources(Request $request)
    {
        // Array to be returned
        $resources_output = [];

        $only_not_restricted = $request->slug === Role::anonymousRoleSlug();

        // Resources mapped in config/authorization.php
        $resources = Authorization::getResources($only_not_restricted);

        // We make the necessary changes in the object
        foreach ($resources as $key => $value) {
            /**
             * We should not return the dummyActionTest in the list of available resources
             * @see tests/feature/AuthorizationTest.php and @see app/Http/Controllers/Samples/DummyActionController.php
             */
            if (isset($value['dummy']) && $value['dummy'] === true) {
                continue;
            }

            // Here we replace the actions declared in config/authorization.php
            // with possible corresponding concrete actions stored in the DB
            // because we need its IDs to store in the relation table role_actions
            $concreteActions = Action::where('resource_slug',$key)->get();
            $resources[$key]['slug'] = $key;
            $resources[$key]['actions'] = $concreteActions;

            // We convert the key value array in a index value array. This format is easier to handled by a client
            $resources_output[] = $resources[$key];
        }
        return $resources_output;
    }

    /**
    * Get the actions available using the GenericService trait
    * @see App\Http\Controllers\Traits\GenericService.php
    * @param request inject the request data
    * @return array actions
    **/
    public function actions(Request $request)
    {
        $query = \App\Authorization\Action::query();
        return $this->getResults($request, $query); // this method is in the GenericService trait
    }
}
