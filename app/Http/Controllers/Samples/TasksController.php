<?php

namespace App\Http\Controllers\Samples;

use Illuminate\Http\Request;

use App\Task;
use App\Http\Requests;
use App\Http\Controllers\CrudController;
use Illuminate\Database\Eloquent\Model;;
use App\Http\Traits\GenericService;

class TasksController extends CrudController
{

    use GenericService;

    public function __construct()
    {
         /**
         * Defines the attributes that GenericService must not automatically add as filter to the query
         *
         * @var array
         */
        $this->gsSkipFilters = ['dateStart', 'dateEnd'];

        /**
         * Defines the default attributes operator that will be used by GenericService when gsApplyFilters is called
         *
         * @var array
         */
        $this->gsAttributesDefaultOperator = ['description'=>'like'];
    }

    protected function getModel()
    {
        return Task::class;
    }

    protected function applyFilters(Request $request, $query) {

        // Uses GenericService to add all filters based in the from request data,
        // skipping $this->gsSkipFilters (dateStart and dateEnd) to be treated manually
        // and automatically adds the $query filters to project_id, description, 'done', 'priority'
        // This avoid having to add each filter to the query manually
        $this->gsApplyFilters($request, $query);

        // Filters that has special wheres, so we have to add them manually
        if ($request->has('dateStart')) {
            $query = $query->where('scheduled_to', '>=', \DryPack::parseDate($request->dateStart));
        }

        if ($request->has('dateEnd')) {
            $query = $query->where('scheduled_to', '<=', \DryPack::parseDate($request->dateEnd));
        }
    }

    protected function beforeSearch(Request $request, $dataQuery, $countQuery) {
        $dataQuery->orderBy('description', 'asc');
    }

    /**
     * Return the validation rules to save a task
     *
     * @param Request $request
     * @param Model $task
     * @return array of validation rules
     */
    protected function getValidationRules(Request $request, Model $task)
    {
        $rules = [
            'description' => 'required|max:256',
            'priority' => 'required|min:1',
            'scheduled_to' => 'required'
        ];

        if (strpos($request->route()->getName(), 'tasks.update') !== false) {
            $rules['done'] = 'required';
        }

        return $rules;
    }

    /**
     * Set the default status of done before save
     *
     * @param Request $request
     * @param Model $task
     * @return void
     */
    public function beforeStore(Request $request, Model $task)
    {
        $task->done = false;
    }

    /**
     * Update the task status
     */
    public function toggleDone(Request $request)
    {
        $task = Task::find($request->id);

        $this->validate($request, ['done' => 'required']);

        $task->done = $request->done;

        $task->save();
    }
}
