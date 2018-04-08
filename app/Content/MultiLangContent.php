<?php

namespace App\Content;

use App\BaseModel;
use App\Content\Content;
use App\User;
use Illuminate\Database\Eloquent\Model;

class MultiLangContent extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'multi_lang_contents';

    protected $translationRelationTarget = null;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['type', 'owner_id'];

    /**
    * Return the relationship with the class defined in the $translationRelationTarget property
    * As the Content class can be parent of multiple classes. Before consuming it, it is necessaty
    * to define the target relation calling setTranslationRelationTarget
    */
    public function translations()
    {
        return $this->hasMany($this->translationRelationTarget);
    }

    /**
    * Return the relationship with the parent project
    */
    public function author()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

    /**
     * Define the target relation to be used
     *
     * @param string $relationTarget - class with name space, like 'App\Section'
     * @return void
     */
    public function setTranslationRelationTarget($relationTarget)
    {
        $this->translationRelationTarget = $relationTarget;
    }

    /**
     * Override the base toArray method to include custom attributes
     *
     * @return array with model's data
     */
    public function toArray() {
        $data = parent::toArray();
        if(isset($data["translations"])){
            $data["locales"] = array_pluck($data["translations"], "locale");
            $data["urls"] = array_pluck($data["translations"], "url", "locale");
        }
        return $data;
    }

}
