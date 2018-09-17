<?php

namespace App\Content;

use App\Content\Content;
use App\BaseModel;

class MediaText extends BaseModel
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
    protected $fillable = ['title','desc','locale','tags', 'media_id'];


    /**
     * Get the Media that owns the media text.
     */
    public function media()
    {
        return $this->belongsTo('App\Content\Media');
    }

}
