<?php
/**
 * CrudController is a shared base controller that provides a CRUD basis for Laravel applications.
 *
 * @author Jamie Rumbelow <jamie@jamierumbelow.net>
 * @license http://opensource.org/licenses/MIT
 */

namespace App\Http\Traits;

use Illuminate\Http\Request;

/**
 * Actions are the core of the CRUD functionality; the methods accessed directly through the router.
 *
 * @internal
 * @uses \App\Http\Controllers\CrudController
 * @used-by \App\Http\Controllers\CrudController
 */
trait Actions
{
    /**
     * List the resources
     *
     * @param \Illuminate\Http\Request  $request
     * @return array containing items and total
     */
    public function index(Request $request)
    {
        $this->callback('beforeAll', $request);

        $klass = $this->getModel();

        $baseQuery = $klass::query();

        $this->callback('applyFilters', $request, $baseQuery);

        $dataQuery = clone $baseQuery;
        $countQuery = clone $baseQuery;

        $this->callback('beforeSearch', $request, $dataQuery, $countQuery);

        if ($request->has('perPage') && $request->has('page')) {
            $data['items'] = $dataQuery
                ->skip(($request->page - 1) * $request->perPage)
                ->take($request->perPage)
                ->get();

            $data['total'] = $countQuery
                ->count();
        } else {
            if ($request->has('limit')) {
                $data = $dataQuery->take($request->limit)->get();
            } else {
                $data = $dataQuery->get();
            }
        }

        $customData = $this->callback('afterSearch', $request, $data, $klass);
        if($customData !== null){
            return $customData;
        }
        return $data;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Illuminate\Database\Eloquent\Model
     * @throws Exception duplicatedResourceError
     */
    public function store(Request $request)
    {
        $this->callback('beforeAll', $request);

        $klass = $this->getModel();
        $obj = new $klass();

        return $this->saveOrUpdate($request, $obj, 'Store');
    }

    /**
     * Display the specified resource.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return Illuminate\Database\Eloquent\Model
     */
    public function show(Request $request, $id)
    {
        $this->callback('beforeAll', $request);

        $klass = $this->getModel();

        $obj = $klass::findOrFail($id);

        $this->callback('afterShow', $request, $obj);

        return $obj;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param integer $id
     * @return Illuminate\Database\Eloquent\Model
     */
    public function update(Request $request, $id)
    {
        $this->callback('beforeAll', $request);

        $klass = $this->getModel();
        $obj = $klass::find($id);

        return $this->saveOrUpdate($request, $obj, 'Update');
    }

    /**
     * Save or update a resource in a transaction executing
     * the before and after callbacks inside the transaction
     *
     * @param \Illuminate\Http\Request $request
     * @return Response
     * @param string $action
     * @return Illuminate\Database\Eloquent\Model
     */
    protected function saveOrUpdate(Request $request, $obj, $action)
    {
        $this->callback('beforeAll', $request);

        $this->callback('beforeValidation', $request, $obj);

        $input = $request->all();

        $messages = [];
        if(method_exists($this,'messages')) {
            $messages = $this->messages($request);
        }

        $this->validate($input, $this->getValidationRules($request, $obj),$messages);
        $obj->fill($input);

        try {
            \DB::transaction(function () use ($request, $action, $obj) {
                $this->callback('before'.$action, $request, $obj);
                $this->callback('beforeSave', $request, $obj);

                $obj->save();

                $this->callback('after'.$action, $request, $obj);
                $this->callback('afterSave', $request, $obj);
            });
        } catch (Exception $e) {
            return Response::json(['error' => 'messages.duplicatedResourceError'], HttpResponse::HTTP_CONFLICT);
        }

        $freshed = $obj->fresh();
        $this->callback('afterFresh', $request, $freshed);

        $response = response($freshed);

        if($request->warning != null) {
            $response->header("Warning", $request->warning);
        }

        return $response;
    }

    /**
     * Destroy a resource using a transaction and calling the callbacks inside the transaction
     *
     * @param \Illuminate\Http\Request $request
     * @param integer $id
     * @return void
     */
    public function destroy(Request $request, $id)
    {
        $this->callback('beforeAll', $request);

        $klass = $this->getModel();
        $obj = $klass::find($id);

        \DB::transaction(function () use ($request, $obj) {
            $this->callback('beforeDestroy', $request, $obj);

            $obj->delete();

            $this->callback('afterDestroy', $request, $obj);
        });
    }
}
