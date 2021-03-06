<?php
/**
 * CrudController is a shared base controller that provides a CRUD basis for Laravel applications.
 *
 * @author Jamie Rumbelow <jamie@jamierumbelow.net>
 * @license http://opensource.org/licenses/MIT
 */

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

/**
 * Callback support
 *
 * @internal
 * @uses \App\Http\Controllers\CrudController
 * @used-by \App\Http\Controllers\CrudController
 */
trait Callbacks
{
    /**
     * The callback registry
     *
     * @var array
     **/
    protected $registeredCallbacks = [
        'beforeAll' => [ 'beforeAll' ],
        'beforeStore' => [ 'beforeStore' ],
        'beforeSearch' => [ 'beforeSearch' ],
        'beforeUpdate' => [ 'beforeUpdate' ],
        'beforeSave' => [ 'beforeSave' ],
        'beforeDestroy' => [ 'beforeDestroy' ],
        'afterStore' => [ 'afterStore' ],
        'afterUpdate' => [ 'afterUpdate' ],
        'afterSave' => [ 'afterSave' ],
        'afterShow' => [ 'afterShow' ],
        'afterDestroy' => [ 'afterDestroy' ],
        'applyFilters' => ['applyFilters'],
        'beforeValidation'=> ['beforeValidation']
    ];

    /**
     * Placeholder callbacks. These are included in the registry by default.
     *
     * @param \Illuminate\Http\Request $request The request object
     * @param \Illuminate\Database\Eloquent\Model $model The model object
     * @return null
     */

    protected function beforeAll(Request $request) { } // NOSONAR
    protected function beforeSearch(Request $request, $dataQuery, $countQuery) { } // NOSONAR
    protected function beforeStore(Request $request, Model $model) { } // NOSONAR
    protected function beforeUpdate(Request $request, Model $model) { } // NOSONAR
    protected function beforeSave(Request $request, Model $model) { } // NOSONAR
    protected function beforeDestroy(Request $request, Model $model) { } // NOSONAR
    protected function afterStore(Request $request, Model $model) { } // NOSONAR
    protected function afterUpdate(Request $request, Model $model) { } // NOSONAR
    protected function afterSave(Request $request, Model $model) { } // NOSONAR
    protected function afterShow(Request $request, Model $model) { } // NOSONAR
    protected function afterDestroy(Request $request, Model $model) { } // NOSONAR
    protected function applyFilters(Request $request, $query) { } // NOSONAR
    protected function beforeValidation(Request $request, Model $model) { } // NOSONAR

    /**
     * Process callbacks for $eventName
     *
     * @param string $eventName
     **/
    protected function callback($eventName)
    {
        if (!isset($this->registeredCallbacks[$eventName])) {
            throw new \App\Exceptions\CallbackException("Unknown callback: $eventName");
        }

        $parameters = array_slice(func_get_args(), 1);
        $callbacks = $this->registeredCallbacks[$eventName];
        $response = null;

        foreach ($callbacks as $callback) {
            $response = call_user_func_array(is_callable($callback) ? $callback : [ $this, $callback ], $parameters);
        }

        return $response;
    }
}

