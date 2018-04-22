<?php

namespace App\Content;

use App\BaseModel;
use App\Content\MultiLangContent;
use App\User;

use Illuminate\Database\Eloquent\Model;

class Section extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'sections';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['title', 'url', 'locale', 'multi_lang_content_id', 'has_single', 'content'];

    /**
    * Return the relationship to the project to which the the task belongs to
    * @return object
    */
    public function multiLangContent()
    {
        return $this->belongsTo(MultiLangContent::class);
    }

    /**
    * Return the relationship to the users
    * @return object
    */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_section', 'section_id', 'user_id');
    }
}
