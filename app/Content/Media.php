<?php

namespace App\Content;

use App\Content\Content;
use App\BaseModel;

class Media extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = "medias";

     /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['file_name','type','status','author_name','author_id', 'mimetype','length'];


    /**
    * Return the relationship with the parent project
    */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

}
