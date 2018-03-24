<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use OwenIt\Auditing\Log;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Carbon\Carbon;
use App\Http\Traits\GenericService;
use App\Util\DbTool;

class DynamicQueryController extends Controller
{
    use GenericService;



    public function __construct()
    {
        /**
         * Defines the attributes that GenericService must not automatically add the attributes in array as filter to the query
         *
         * @var array
         */
        $this->gsSkipFilters = ['model'];
    }


    public function index(Request $request)
    {
        // As we are using GenericService, the filters passed are in a json format
        $modelFilters = json_decode($request->filters, true);

        // from the filters, at this moment, we just want to retrive, the model id
        // that represent the namespace and class name
        $modelFilters = is_array($modelFilters)? $modelFilters[0]: $modelFilters;
        $modelWithNameSpace = $modelFilters["model"]["id"];

        // Instantiate the model using the model name and the default namespace
        $instance = new $modelWithNameSpace;

        // Get a generic query using the model table name
        // We do not use the model query because we do not want
        // to run the model data, relations and so on.
        // We just wanna get the data from the raw model table
        $baseQuery = \DB::table($instance->getTable());

        if(isset($instance) && isset($baseQuery)) {
            // Get all model attributes
            $all_attr = $instance->getAllAttributes();

            // Filter the attributes defined as non visible in dynamic query
            $filtered_attr = array_diff($all_attr, $instance->getHideAttributesInDynamicQuery());

            // Define the filtered attributes as the fields to be in the select query
            $baseQuery = call_user_func_array(array($baseQuery, "select"), $filtered_attr);

            // We use the method in the App\Http\Traits\GenericService trait
            // (injected in this class) to get the results
            return $this->getResultsUsingDBQuery($request, $baseQuery);
        } else {
            return [
                'items'=>[],
                'total'=>0
            ];
        }
    }

    /**
     * Action responsible for taking all the models with its attributes' name and type
     *
     * @return array containing a list of models with its attributes
     */
    public function models(Request $request)
    {
        $models = \DryPack::loadableModels(); // all except BaseModel
        $data = array();

        foreach($models as $model) {
            // Instantiate the model using the model name and the default namespace
            $instance = new $model;
            // Get a generic query using the pluralized model name
            $baseQuery = \DB::table($instance->getTable());

            if(isset($baseQuery) && isset($instance)) {

                // Get the table columns using the Generic Service method
                $columnWithTypes = DbTool::getTableColumnsFromDBQueryBuilder($baseQuery);
                $hide_attributes_in_dynamic_query = collect($instance->getHideAttributesInDynamicQuery());

                // Filter the columns that can not be shown as defined in in model
                $columnWithTypes = $columnWithTypes->filter(function ($attribute, $key) use ($hide_attributes_in_dynamic_query) {
                    return !$hide_attributes_in_dynamic_query->contains($attribute->name);
                });

                array_push($data, [
                    'name' => $model,
                    'props' => $columnWithTypes->all()
                ]);
            }
        }
        return [
            'models' => $data
        ];
        //return $data;
    }
}
