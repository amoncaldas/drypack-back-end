<?php

namespace App\Listeners;

use OwenIt\Auditing\Events\Auditing;

class AuditingListener
{
    /**
     * Create the Auditing event listener.
     */
    public function __construct()
    {
        // ...
    }

    /**
     * Handle the Auditing event.
     *
     * @param \OwenIt\Auditing\Events\Auditing $event
     * @return void
     */
    public function handle(Auditing $event)
    {
        // if audits is disabled for whole application
        // we return false and this change will not be audit/stored
        $disableAudit = env("DISABLE_AUDITS");
        if($disableAudit === true) {
            return false;
        }

        // if audits is disabled for this model
        // we return false and this change will not be audit/stored
        if($event->model->disableAudits === true) {
            return false;
        }

        // if changed has been made in non watched attributes
        // we return false and this change will not be audit/stored
        return $this->hasValidChange($event->model);
    }

    /**
     * Verifies if the model has valid change. If not, this should not be audit
     *
     * @param object $model
     * @return boolean
     */
    public function hasValidChange($model) {

        // to get the original data (before change) there must be an accessor
        // defined. In Base model is defined the method 'getOriginalAttribute'
        // so, if the the model extends from the BaseModel, we can get the original
        // if is not extending BaseModel, this method must be implemented as public
        $original = $model->original;

        if (isset($original) && is_array($original)){
            // compare the current attributes value with the original (before changed) and get what has changed
            $diff_attr = array_diff($model->getAttributes(), $original);

            // get a list of not watched model attributes
            $exclude = ["updated_at", "created_at", "deleted_at"];

            // the accessor to get the fields excluded is defined by the laravel audit component
            if (method_exists($model, "getAuditExclude")){
                $exclude = array_merge($model->getAuditExclude(), $exclude);
            }

            // Get the changed data attributes
            $diff_keys = array_keys($diff_attr);

            // compare the attributes changed and the attributes that should be watched
            $watched_attributes_changed = array_diff($diff_keys, $exclude);

            // if there was no attribute changed or the attribute changed are not watched
            // then we return false and this change will not be audit/stored
            if(count($diff_attr) === 0 || count($watched_attributes_changed) === 0){
                return false;
            }
        }
        return true;
    }
}
