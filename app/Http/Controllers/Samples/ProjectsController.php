<?php

namespace App\Http\Controllers\Samples;

use App\Project;

use Hash;
use Log;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\CrudController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;

class ProjectsController extends CrudController
{
    public function __construct()
    {
    }

    protected function getModel()
    {
        return Project::class;
    }

    protected function applyFilters(Request $request, $query)
    {
        $query = $query->with('tasks');

        if ($request->has('name')) {
            $query = $query->where('name', 'like', '%'.$request->name.'%');
        }
    }

    protected function beforeSearch(Request $request, $dataQuery, $countQuery)
    {
        $dataQuery->orderBy('name', 'asc');
    }

    protected function getValidationRules(Request $request, Model $obj)
    {
        $rules = [
            'name' => 'required|max:100|unique:projects',
            'cost' => 'required|min:1'
        ];

        if (strpos($request->route()->getName(), 'projects.update') !== false) {
            $rules['name'] = 'required|max:255|unique:projects,name,'.$obj->id;
        }

        return $rules;
    }
}
