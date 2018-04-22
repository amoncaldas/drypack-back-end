<?php
/**
 * GenericService is a shared base service trait that provides out of the functionalities to get data based in the request
 *
 * @author Amon Caldas <amoncaldas@gmail.com>
 * @license http://opensource.org/licenses/MIT
 */


namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder as EloquentQueryBuilder;
use Illuminate\Database\Query\Builder as DataBaseQueryBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use App\Exceptions\BusinessException;
use Illuminate\Support\Collection;
use App\Util\DbTool;

/**
 * GenericService is the services provided by the application to query models without creating manual queries
 *
 * @internal
 */

 /**
 * App\Http\Traits\GenericService
 *
 * @property array $gsSkipFilters
 * @property array $gsAttributesDefaultOperator
 * @property boolean $gsSkipFiltersValidation
 *
 * @method getResults(Request $request, EloquentQueryBuilder $eloquent_query_builder)
 * @method getResultsUsingDBQuery(Request $request, DataBaseQueryBuilder $query)
 * @method gsApplyFilters(Request $request, EloquentQueryBuilder $eloquent_query)
 * @method gsApplyFiltersInDBQueryBuilder(Request $request, DataBaseQueryBuilder $query)
 * @method applyLimits(Request $request, DataBaseQueryBuilder $query) *
 * @method addAttributeFilters(Request $request, DataBaseQueryBuilder $query)
 * @method validateFilters(Request $request, DataBaseQueryBuilder $query)
 * @method getAttributeFilters(Request $request)
 * @method mapAndGet(Request $request, $method)
 *
 * @method protected buildResult($total, $itemsCount, $items)
 * @method protected throwDescriptiveError(\Exception $e, DataBaseQueryBuilder $query)
 *
 * @method private prepareBinding($filter, DataBaseQueryBuilder $query)
 * @method private getMultipleFilters(Request $request)
 * @method private getSingleFilter(Request $request)
 * @method private getRequestKeyValueFilters(Request $request)
 * */
trait GenericService
{
    /**
     * Defines the attributes that GenericService must not automatically add
     * the attributes in array as filter to the query
     *
     * @var array
     */
    protected $gsSkipFilters = [];

    /**
     * Defines the default attributes operator that will be used by
     * GenericService when gsApplyFilters is called
     *
     * @var array
     */
    protected $gsAttributesDefaultOperator = [];

    /**
     * This tells the GenericService to do not validate the filters
     * @var boolean
     */
    protected $gsSkipFiltersValidation = false;

    /**
     * Get the results for a given service request
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Illuminate\Database\Eloquent\Builder $eloquent_query_builder
     * @param  bool $validate_filters verify if each filter exist as a property/column in the target entity/table
     * @return array
     * @throws BusinessException
     */
    public function getResults(Request $request, EloquentQueryBuilder $eloquent_query_builder){
        $total = $itemsCount = 0; // the default offset (page), total of items (with no filters) and count of items (with filters)
        $items = []; // The initial collection of items returned is empty

        // apply the filters coming from the request
        $this->gsApplyFilters($request, $eloquent_query_builder);

        // Get DataBaseQueryBuilder from $eloquent_query_builder
        $db_query_builder = $eloquent_query_builder->getQuery();

        // Tries to get the results
        try{
            // Get the total, not considering pagination
            $total = $eloquent_query_builder->count();

            // Apply limits/pagination and get the data, using pagination
            $this->applyLimits($request, $db_query_builder);
            $eloquent_query_builder->setQuery($db_query_builder);

            // Get the item using EloquentQueryBuilder, so each item will be an instance of the eloquent model
            $items = $eloquent_query_builder->get()->all();

            $itemsCount = count($items);
        }
        catch(\Exception $e){
            // If we had an error while executing the query, set the error message to be returned
            $this->throwDescriptiveError($e, $db_query_builder);
        }

        //we return an array with the values. In all cases we return this array
        return $this->buildResult($total, $itemsCount, $items);
    }

    /**
    * @param  Illuminate\Database\Query\Builder $query
    * @return Illuminate\Support\Collection $columns_with_type
    */
    public function getResultsUsingDBQuery(Request $request, DataBaseQueryBuilder $query){
        $total = $itemsCount = 0; // the default offset (page), total of items (with no filters) and count of items (with filters)
        $items = []; // The initial collection of items returned is empty

        // apply the filters coming from the request
        $this->gsApplyFiltersInDBQueryBuilder($request, $query);

        // Tries to get the results
        try{
            // get the total, not considering pagination
            $total = $query->count();

            // Apply limits/pagination and get the data, using pagination
            $this->applyLimits($request, $query);
            $items = $query->get()->all();
            $itemsCount = count($items);
        }
        catch(\Exception $e){
            $this->throwDescriptiveError($e, $query);
        }

        //we return an array with the values. In all cases we return this array
        return $this->buildResult($total, $itemsCount, $items);
    }

    /**
     * Apply the attributes filters coming from the request to the $query,
     * either from from request
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function gsApplyFilters(Request $request, EloquentQueryBuilder $eloquent_query){
        $db_query = $eloquent_query->getQuery();
        $this->gsApplyFiltersInDBQueryBuilder($request, $db_query);
        $eloquent_query->setQuery($db_query);
    }


    /**
     * Apply the attributes filters coming from the request to the $query,
     * either from from request
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @param  bool $validate_filters verify if each filter exist as a property/column in the target entity/table
     * @return void
     */
    public function gsApplyFiltersInDBQueryBuilder(Request $request, DataBaseQueryBuilder $query){

        // Here we validate the filters to check if it has anyone invalid (that is not present in the database)
        if($this->gsSkipFiltersValidation === false) {
            $this->validateFilters($request, $query);
        }

        // Here we add the filters coming from the request to the QueryBuilder.
        // $query is passed as reference (can me modified)
        $this->addAttributeFilters($request, $query);
    }

    /**
     * Apply the limit filters coming from the request to the $query
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @param  bool $validate_fields verify if each filter exist as a property/column in the target entity/table
     * @return void
     */
    public function applyLimits(Request $request, DataBaseQueryBuilder $query){

        $take = 20; //the default page size (amount of items)
        $skip = 0; // the default offset (page), total of items (with no filters) and count of items (with filters)

        if ($request->has('perPage') && $request->has('page')) {
            $query = $query->skip(($request->page - 1) * $request->perPage)->take($request->perPage);
        }
        else {
            if ($request->has('limit')) {
                $take = $request->limit;
            }
            elseif ($request->has('take')) {
                $take = $request->take;
            }
            if ($request->has('skip')){
                $skip = $request->skip;
            }

            $query = $query->take($take);
            $query = $query->skip($skip);
        }
    }

    /**
     * Add the filters coming from the request to the $query,
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function addAttributeFilters(Request $request, DataBaseQueryBuilder $query){

        // closure to add where to query, applying the filters from the request
        // we add the filter and return the binding value (the parameter that will be passed to the db)
        // $addFilter = function(&$filter) use (&$query){
        $addFilter = function($filter) use ($query){
            $this->prepareBinding($filter, $query);

            if(!isset($filter->invalid) || $filter->invalid = false) {
                $bindings[] = $filter->value;
                return $bindings;
            }
        };

        // here we get the filters from the request
        $filters = $this->getAttributeFilters($request);
        $bindings = [];
        foreach ($filters as $filter) {
            $filterBinding = $addFilter($filter);
            if(isset($filterBinding)) {
                $bindings[] = $filterBinding;
            }

        }
        // here we set the parameters array as bindings
        $query->setBindings($bindings);
    }


    /**
     * Validate the filters coming from the request.
     * It is used before adding the parameters to the $query
     * The validation compares the filters from the request with the table structure
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @throws BusinessException
     */
    public function validateFilters(Request $request, DataBaseQueryBuilder $query){
        $error = null; // the default error string in null (no error)
        $filters = $this->getAttributeFilters($request);// get the filters

        if (count ($filters) === 0) return; // if there is no filter, we shall not continue

        // here we check if each filter prop exists as a column in the table
        // if not, we stop in the first fail and build a message telling that the filter is not valid
        // we also list the columns and data type of each column that can be used as a filter

        $columns = DbTool::getTableColumnsFromDBQueryBuilder($query)->pluck("name")->all();

        // In some cases the schema does not return the columns list (sqlserver views, for example)
        // In these cases we can not validate the filters
        if(count($columns) > 0){
            foreach($filters as $filter) {
                if(!in_array($filter->prop,$columns)) {
                    $error = "Invalid property '$filter->prop' for entity $query->from. ";
                    $error .= DbTool::getEntityDesc($query);
                    break;
                }
            }
        }

        if(isset($error)){
            throw new BusinessException($error);
        }
    }

    /**
     * Get the filter from the request
     * It is used to extract the the collection of filters from the request
     * either from a collection (array of json object in url) either from a collection of three get parameters ('prop', 'op' and 'value')
     * examples:
     *  ?filters=[{"prop":"id","op":"=","value":"4032136"}, {"prop":"id","op":"=","value":"4032136"}];
     *  ?prop=id&op==&value=4032136
     *  ?name=rafael
     *
     * @param  \Illuminate\Http\Request $request
     * @return array $filters
     */
    public function getAttributeFilters(Request $request){

        //example url: v1/domain/<resource>?filters=[{"prop":"id","op":"=","value":"4032136"}, {"prop":"id","op":"=","value":"4032136"}]
        $multiple = $this->getMultipleFilters($request);

        //example url: v1/domain/<resource>?prop=id&op==&value=4032136
        $single = $this->getSingleFilter($request);

        //example url: v1/domain/<resource>?name=rafael
        $key_value_filters = $this->getRequestKeyValueFilters($request);

        $all_filters = array_merge($multiple, $single, $key_value_filters);
        return $all_filters;
    }

    /**
     * Get the value of a filter key, if available
     *
     * @param Request $request
     * @param string $filterKey
     * @return string|null
     */
    public function gsGetfilterValue(Request $request, $filterKey) {
        $filters = $this->getAttributeFilters($request);

        foreach ($filters as $key => $value) {
            if ($key === $filterKey) {
                return $value;
            }
            if (is_object($value)) {
                if(isset($value->$filterKey)) {
                    return $value->$filterKey;
                }
            }
        }
    }


    /**
    * Map the request to the appropriated controller method
    * for example: /part-of-url/health-units will be routed to the method @healthUnits
    * @param request inject the request data
    * @param domainName the domain name that will be converted to a controller method
    * @return array containing items, total and items_count
    **/
    public function mapAndGet(Request $request, $method) {
        $domainMethod = camel_case($method);
        if(method_exists($this, $domainMethod)){
            return $this->$domainMethod($request);
        }
        // if method does not exist, return not found
        abort(404, 'Resource not found.');
    }

     /**
     * Build the result used in the response
     *
     * @param integer $total
     * @param integer $itemsCount
     * @param array $items
     * @return void
     */
    protected function buildResult($total, $itemsCount, $items) {
        return [
            'total'=>$total,
            'items_count'=>$itemsCount,
            'items'=>$items
        ];
    }

    /**
     * Compose and throw an error message describing the fail while executing the query
     *
     * @param \Exception $e
     * @param DataBaseQueryBuilder $query
     * @return void
     * @throws BusinessException
     */
    protected function throwDescriptiveError(\Exception $e, DataBaseQueryBuilder $query){
        // if we had an error while executing the query, set the error message to be returned
        $error = $e->getMessage();
        $error .= ". ". DbTool::getEntityDesc($query);
        throw new BusinessException($error);
    }

     /**
     * Internal function prepare binding value.
     * If the operator requires wrappers, we add them (like, ilike, startswith and endswith)
     *
     * @param stdClass $filter
     * @param Illuminate\Database\Eloquent\Builder $query
     */
    private function prepareBinding($filter, DataBaseQueryBuilder $query){
        if (in_array($filter->op, ['like','ilike', 'startswith', 'endswith'])) {
            // whereRaw expects an array of parameters.
            // We only have one, so we put it in an array
            if($filter->op === 'ilike') {
                $filter->value = ['%'.strtolower($filter->value).'%'];
                 // we set the whereRaw condition, but the left value (filter->value)
                // will be binded via parameter binding, replacing the ?
                $query = $query->whereRaw("lower($filter->prop) like ?");

            } elseif($filter->op === 'like') {
                $filter->value = ['%'.$filter->value.'%'];
                // we set the where condition, but the left value (filter->value)
                // will be binded via parameter binding, replacing the ?
                $query = $query->whereRaw("$filter->prop like ?");
            } else {
                if ($filter->op === 'startswith') {
                    $filter->value = ['%'.strtolower($filter->value)];
                } else { // endswith
                    $filter->value = [strtolower($filter->value).'%'];
                }
                $query = $query->whereRaw("lower($filter->prop) like ?");
            }
        }
        elseif ($filter->op === 'notin') {
            if ($filter->value !== null ) {
                if (is_array($filter->value) ) {
                    $query = $query->whereNotIn($filter->prop, $filter->value);
                } elseif(strpos($filter->value, ',') !== false) {
                    $query = $query->whereNotIn($filter->prop, explode(',', $filter->value));
                } else {
                    $filter->invalid = true;
                }
            } else {
                $filter->invalid = true;
            }
        }
        else{
            $filter->value = $filter->value;
            $query = $query->where($filter->prop, $filter->op, '?');
        }
    }

    /**
     * Get the filters from the request
     * It is used to extract the the collection of filters from the request
     * It looks for a collection of filters as a json GET parameter
     *
     * @param  \Illuminate\Http\Request $request
     * @return array $filters
     */
    private function getMultipleFilters(Request $request){
        $filters = [];
        $defaultOperator = "=";

        //handles a filter collection
        //example url: v1/domain/<resource>?filters=[{"prop":"id","op":"=","value":"4032136"}, {"prop":"id","op":"=","value":"4032136"}]
        if($request->has('filters')) {
            $filters = $request->input('filters');
            if(!is_object($filters)){
                $filters = json_decode(($filters));
                $filters = is_array($filters)? $filters : [$filters];
                foreach ($filters as $filter) {
                    if(!isset($filter->op)){
                        $filter->op = $defaultOperator; // set default operator, if not set
                    }
                }
            }
        }

        return $filters;
    }

    /**
     * Get the single filter from the request as a collection of three GET parameters (prop;op;value)
     * @param  \Illuminate\Http\Request $request
     * @return array $filters
     */
    private function getSingleFilter(Request $request){
        //handles a unique filter parameters
        //example url: v1/domain/<resource>?prop=id&op==&value=4032136
        $filters = [];
        $defaultOperator = "=";

        if($request->has('prop') && $request->has('value')) {
            $filter = new \stdClass();
            $filter->op = $defaultOperator; // default operator

            $filter->value = $request->input('value');
            $filter->prop = $request->input('prop');
            if($request->has('op') ){
                $filter->op = $request->input('op');// override the default operator, if available
            }

            $filters = array($filter);
        }
        return $filters;
    }

    /**
     * Get the single filter from the request as a collection of three GET parameters (prop;op;value)
     * @param  \Illuminate\Http\Request $request
     * @return array $filters
     */
    private function getRequestKeyValueFilters(Request $request){

        //example url: v1/domain/<resource>?name=rafael
        $filters = [];

        $except = ['op','value', 'prop', 'filters', 'take','skip','limit', 'perPage', 'page'];
        $except = array_merge($except, $this->gsSkipFilters);

        $request_key_values = $request->except($except);

        foreach ($request_key_values as $key => $value) {

            // Defines the default attribute operator that will be used.
            // If it was not specified in the array in the Controller, '=' is used
            $attribute_operator = isset($this->gsAttributesDefaultOperator[$key])? $this->gsAttributesDefaultOperator[$key] : "=";

            $filter = new \stdClass();
            $filter->op = $attribute_operator;
            $filter->value = $value;
            $filter->prop = $key;
            $filters[] = $filter;
        }
        return $filters;
    }


}
