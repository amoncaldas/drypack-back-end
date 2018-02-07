<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */

namespace App\Authorization;

use Illuminate\Database\Eloquent\Model;
use App\BaseModel;
use App\Authorization\Authorization;


class Action extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'actions';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['action_type_slug','resource_slug'];

    // Without timestamps
    public $timestamps = false;



    /**
     * belongs-to relationship method.
     *
     * @return QueryBuilder
     */
    public function actionType()
    {
        return Authorization::getAction($this->action_type_slug);
    }

    /**
     * belongs-to relationship method.
     *
     * @return QueryBuilder
     */
    public function actionDependencies()
    {
        // Here we map all the actions that are in the table actions_dependencies as a dependency target (depends_on_action_id) and that
        // has as dependence source the current action (dependent_action_id)
        return $this->belongsToMany(Action::class, 'actions_dependencies', 'dependent_action_id','depends_on_action_id');
    }


    /**
     * Overrides toArray method
     *
     * @return Array
     */
    public function toArray() {
        $data = parent::toArray();
        $data['dependencies'] = $this->actionDependencies->toArray();
        $actionType = $this->actionType();
        $data['action_type_name'] = $actionType['name'];
        return $data;
    }

}
