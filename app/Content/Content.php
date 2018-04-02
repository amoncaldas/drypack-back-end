<?php

namespace App\Content;

use App\BaseModel;
use App\User;
use App\Content\Category;
use App\Content\MultiLangContent;
use App\Content\Media;
use App\Content\Section;

abstract class Content extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = null;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'abstract',
        'short_desc',
        'status',
        'password',
        'locale',
        'featured_image_id',
        'multi_lang_content_id',
        'section_id'
    ];

    /**
     * Get the content type of the concreet class
     *
     * @return string
     */
    abstract protected function getContentType();

    /**
    * Return the relationship to the multi laguage content to which the content belongs
    * @return object
    */
    public function multiLangContent()
    {
        return $this->belongsTo(MultiLangContent::class);
    }

    /**
    * Return the relationship to the feaured images of the content
    * @return object
    */
    public function featuredImage()
    {
        return $this->hasOne(Media::class, 'featured_image_id', 'id');
    }

    /**
    * Return the relationship to the medias
    * @return object
    */
    public function medias()
    {
        return $this->belongsToMany(Media::class, 'media_content', 'content_id', 'media_id')
            ->withPivot('content_type')
            ->wherePivot('content_type', $this->getContentType());
    }

    /**
    * Return the relationship to the medias
    * @return object
    */
    public function authors()
    {
        return $this->belongsToMany(User::class, 'author_content', 'content_id', 'user_id')
            ->withPivot('content_type')
            ->wherePivot('content_type', $this->getContentType());
    }



    /**
    * Return the relationship to the section to which the content belongs
    * @return object
    */
    public function section()
    {
        return $this->hasOne(Section::class, 'section_id', 'id');
    }

    /**
    * Return the relationship to the categories to which the content belongs
    * @return object
    */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_content', 'content_id', 'category_id');
    }

    /**
    * Return the relationship to the location to which the content belongs
    * @return object
    */
    public function location()
    {
        // to be implemented
        //return $this->belongsToMany(Category::class, 'section_id', 'id');
    }

}
