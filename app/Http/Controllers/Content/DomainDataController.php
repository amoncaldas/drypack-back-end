<?php

namespace App\Http\Controllers\Content;

use Illuminate\Http\Request;
use App\Http\Traits\GenericService;
use App\Content\Category;
use App\Http\Controllers\Controller;

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
}
