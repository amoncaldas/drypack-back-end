<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use OwenIt\Auditing\Models\Audit;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $baseQuery = Audit::with('user');

        // Filter for the user relation attributes
        $baseQuery = $baseQuery->whereHas('user', function($query) use ($request) {
            if($request->has('user')) {
                $query->where('name',  'ilike', '%'.$request->user.'%');
            }
        });

        if($request->has('event') && isset($request->event)) {
            $baseQuery = $baseQuery->where('event', $request->event);
        }

        if ($request->has('auditable_id')) {
            $baseQuery = $baseQuery->where('auditable_id', $request->auditable_id);
        }

        if($request->has('model') && $request->model !== null) {
            $baseQuery = $baseQuery->where('auditable_type', $request->model);
        }

        if($request->has('dateStart')) {
            $baseQuery = $baseQuery->where('created_at', '>=', $request->dateStart);
        }

        if($request->has('dateEnd')) {
            $baseQuery = $baseQuery->where('created_at', '<=', \DryPack::parseDate($request->dateEnd)->endOfDay());
        }

        $dataQuery = clone $baseQuery;
        $countQuery = clone $baseQuery;

        $skip  = ($request->page - 1) * $request->perPage;
        $take = $request->perPage;

        $data['items'] = $dataQuery
            ->orderBy('created_at', 'desc')
            ->skip($skip)
            ->take($take)
            ->get();

        $data['total'] = $countQuery->count();

        return $data;
    }

    /**
     * Action responsible for returning all the models
     *
     * @return array containing a list of models
     */
    public function models(Request $request)
    {
        // Get all models, except BaseModel
        $models = \DryPack::loadableModels();

        return [
            'models' => $models
        ];
    }
}
