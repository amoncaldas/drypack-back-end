<?php

namespace App\Http\Controllers\Content;

use Illuminate\Http\Request;
use App\Http\Traits\GenericService;
use App\Content\Category;
use App\Http\Controllers\Controller;
use App\Content\ContentStatus;
use App\Authorization\Authorization;
use \Auth;

class DomainDataController extends Controller
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
    * Get the categories available using the GenericService trait
    * @see App\Http\Controllers\Traits\GenericService.php
    * @param request inject the request data
    * @return array actions
    **/
    public function categories(Request $request)
    {
        $query = Category::query();
        $results = $this->getResults($request, $query); // this method is in the GenericService trait
        return $results;
    }

    /**
     * Get all the status that a user can use for a specific content (resource)
     *
     * @param Request $request
     * @return array statuses
     */
    public function contentStatuses (Request $request) {
        $resourceSlug = $this->gsGetfilterValue($request, "resource");

        if ($resourceSlug != null) {

            $allStatuses = ContentStatus::allWithTrans();

            // as each status also represent an action, we
            // check which of the status/action are authorized to the current user and current resource
            foreach ($allStatuses as $status) {
                $action = ContentStatus::getStatusAction($status);

                if (Auth::user()->hasResourcePermission($resourceSlug, $action)) {
                    $statuses[] = $status;
                }
            }

            // return the filtered statuses
            return $statuses;
        }
        return [];
    }
}
