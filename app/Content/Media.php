<?php

namespace App\Content;

use App\Content\Content;

class Media extends Content
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
     * Get the content type of the concreet class
     *
     * @return string
     */
    protected function getContentType() {
        return "media";
    }

    /**
    * Return the relationship with the parent project
    */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

}
