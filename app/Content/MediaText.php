<?php

namespace App\Content;

use App\Content\Content;

class MediaText extends Content
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = "medias_text";

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['title','desc','locale','media_id'];

    /**
     * Get the content type of the concreet class
     *
     * @return string
     */
    protected function getContentType() {
        return "media_text";
    }


    /**
     * Get the Media that owns the media text.
     */
    public function media()
    {
        return $this->belongsTo('App\Content\Media');
    }

}
