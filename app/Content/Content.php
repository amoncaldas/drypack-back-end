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
    protected $table = "contents";

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'locale',
        'section_id',
        'slug',
        'order',
        'content_type',
        'content',
        'abstract',
        'short_desc',
        'status',
        'password',
        'featured_image_id',
        'multi_lang_content_id',
    ];

    /**
     * Get the content type of the concreet class
     *
     * @return string
     */
    abstract protected function getContentType();

    /**
     * Get the target relation class with namespace
     *
     * @return string
     */
    abstract public function getTranslationRelationTarget();


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
        return $this->belongsToMany(User::class, 'author_content', 'content_id', 'author_id')
            ->withPivot('content_type')
            ->wherePivot('content_type', $this->getContentType());
    }



    /**
    * Return the relationship to the section to which the content belongs
    * @return object
    */
    public function section()
    {
        return $this->hasOne(Section::class, 'id', 'section_id');
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
     * Get the content url
     *
     * @return string
     */
    public function url(): string {
        // initially, the section url is the one stored in the database
        $section_url = $this->section->url;

        // if the section in question is not the default, the locale is prepended to the section url
        if ($this->section->locale !== env("DEFAULT_LOCALE")) {
            $section_url = "/".strtolower($this->section->locale).$section_url;
        }

        // build the content url
        return "$section_url/$this->content_type/$this->slug/$this->id";
    }

    /**
     * Get the urlof all content locale version
     * This method must be used with caution, because consumes a lot of resources!
     *
     * @return array
     */
    public function urls(): array {
        $urls = [];
        // load the parent multi langue content entity model
        $mlc = $this->multiLangContent;

        // set the translation relation model
        $mlc->setTranslationRelationTarget($this->getTranslationRelationTarget());

        // iterate and populate the url with the urls using the locale as key
        foreach ($mlc->translations as $translation) {
            $urls[$translation->locale] = $translation->url();
        }
        return $urls;
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

    /**
     * Override the base toArray method to include custom attributes
     *
     * @return array with model's data
     */
    public function toArray() {
        $data = parent::toArray();
        $data["url_segments"] = [
            "slug" => $data["slug"],
            "section_id" => $data["section_id"],
            "content_id" => $data["id"]
        ];
        $data["url"] = $this->url();
        return $data;
    }
}
