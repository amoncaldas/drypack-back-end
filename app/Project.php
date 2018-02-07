<?php

namespace App;

use App\BaseModel;
use App\Task;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\AuditingTrait;

/**
 * App\Project
 *
 * @property int $id
 * @property string $name
 * @property float $cost
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Task[] $tasks
 * @property-read \Illuminate\Database\Eloquent\Collection|\OwenIt\Auditing\Log[] $logs
 * @method static \Illuminate\Database\Query\Builder|\App\Project whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Project whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Project whereCost($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Project whereUpdatedAt($value)
 */
class Project extends BaseModel
{
    protected $table = 'projects';
    protected $fillable = ['name', 'cost'];

    public function __construct($attributes = array())
    {
        parent::__construct($attributes);

        $this->addCast(['cost' => 'real']);
    }

    /**
    * Return the relationship with the parent project
    */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
