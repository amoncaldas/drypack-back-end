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
        // Get a generic query using the pluralized model name
        // We do ot use the below model query because we do not want
        // to run the model data, relations and so on.
        // We just wanna get the data from the raw model table
        $baseQuery = \DB::table(str_plural(strtolower($request->model)));

        // Instantiate the model using the model name and the default namespace
        $modelWithNameSpace = 'App\\'.$request->model;
        $instance = new $modelWithNameSpace;

        if(isset($instance) && isset($baseQuery)) {
            // Get all model attributes
            $all_attr = $instance->getAllAttributes();

            // Filter the attributes defined as non visible in dynamic query
            $filtered_attr = array_diff($all_attr, $instance->getHideAttributesInDynamicQuery());

            // Define the filtered attributes as the fields to be in the query select
            $baseQuery = call_user_func_array(array($baseQuery, "select"), $filtered_attr);

            // We use the method in the GenericService trait to get the results
            $data = $this->getResultsUsingDBQuery($request, $baseQuery);
            return $data;
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
        $models = \DryPack::modelNames(array("BaseModel.php")); // all except BaseModel
        $data = array();

        foreach($models as $model) {

            // Get a generic query using the pluralized model name
            $table_name = str_plural(strtolower($model));
            $baseQuery = \DB::table($table_name);

            // Instantiate the model using the model name and the default namespace
            $modelWithNamespace = 'App\\'.$model;
            $instance = new $modelWithNamespace;

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

        return $data;
    }
}
