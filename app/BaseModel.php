<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\BusinessException;

class BaseModel extends Model implements AuditableContract
{
    use Auditable;

    protected $auditEnabled  = true;

    // Clear the oldest audits after 50 records.
    protected $auditThreshold = 50;

    // Specify what actions you want to audit.
    protected $auditableEvents = ['created', 'updated', 'deleted', 'saved', 'restored'];

    // Fields that you do NOT want to audit.
    protected $dontKeepAuditOf = ['password'];

    protected $casts = [];

    // format only used to store in the database, not to show
    protected $dateFormat = 'Y-m-d H:i:sO';

    /**
     * Attributes that must not be shown in dynamic query
     *
     * @var array
     */
    protected $hideAttributesInDynamicQuery = ['password'];

     /**
     * Return the list of properties that should not be listed in dynamic queries
     *
     * @return void
     */
    public function getHideAttributesInDynamicQuery()
    {
        return $this->hideAttributesInDynamicQuery;
    }

    /**
     * Allow to add more casts dynamically
     *
     * @param array $more_attributes
     * @return void
     */
    protected function addCast($more_attributes = [])
    {
        $this->casts = array_merge($this->casts, $more_attributes);
    }

    /**
     * Set an attribute value
     *
     * @param string attribute $key
     * @param any attribute $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->dates) && is_string($value)) {
            $this->attributes[$key] = \DryPack::parseDate($value);
        } else {
            parent::setAttribute($key, $value);
        }
    }

    /**
     * Get all the model attributes
     *
     * @return array of attribute names
     */
    public function getAllAttributes(){
        $fillable = $this->getFillable();
        $hidden = $this->getHidden();
        $attributes = array_merge($fillable, $hidden);
        if ($this->timestamps !== false) {
            $attributes[] = "created_at";
            $attributes[] = "updated_at";
        }
        return $attributes;
    }

    /**
     * Get all the model attributes
     *
     * @return array of attribute names
     */
    public function getOriginalAttribute(){
        $original = $this->original;
        return $original;
    }



    /**
     * Create an audit entry for the model, the given event, old and new values
     * This method is based on "owen-it/laravel-auditing" version "~4.1.3" and its default config.
     * If the version of laravel-auditing changes, it is possible that it causes some side effect here.
     * @param  $old_values array containing old values;
     * @param  $new_values array containing new values
     * @param  $value_key value key that indicates which values has changed/stored
     * @param  $event array event to be stored as audit
     * @return void
     * @throws App\Exceptions\BusinessException
     */
    public function storeAudit($event, $value_key, $old_values, $new_values = null)
    {
        if (!isset($old_values)) $old_values = [];
        if (!isset($new_values)) $new_values = [];

        if ($old_values !== $new_values) {
            if(!in_array($event, $this->auditableEvents)){
                throw new BusinessException("The event $event is not listed as an auditable event in App\BaseModel property auditableEvents");
            }

            try{
              sort(json_decode(json_encode($new_values), true));
            } catch(\Exception $ex) {/*silence is gold*/}

            try{
              sort(json_decode(json_encode($old_values), true));
            } catch(\Exception $ex) {/*silence is gold*/}


            $foreignKey = Config::get('audit.user.foreign_key', 'user_id');
            $user = Auth::user();

            $data = $this->transformAudit([
                'auditable_id'   => $this->getKey(),
                'auditable_type' => $this->getMorphClass(),
                $foreignKey      => $user->resolve(),
                'url'            => Request()->fullUrl(),
                'ip_address'     => Request()->ip(),
                'user_agent'     => Request()->header('User-Agent'),
                'new_values'     => [ $value_key => $new_values ],
                'old_values'     => [ $value_key => $old_values ],
                'event'          => $event
            ]);
            Audit::create($data);
        }
    }
}
