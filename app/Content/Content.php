<?php

namespace App\Content;

use App\BaseModel;
use App\User;
use App\Content\Category;
use App\Content\MultiLangContent;
use App\Content\Media;
use App\Content\Section;
use App\Content\ContentStatus;
use \DB;

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
        'tags',
        'status',
        'password',
        'featured_image_id',
        'featured_video_id',
        'multi_lang_content_id',
        'published_at'
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
    * Return the relationship to the feaured image of the content
    * @return object
    */
    public function featuredImage()
    {
        return $this->hasOne(Media::class, 'id', 'featured_image_id');
    }

    /**
    * Return the relationship to the feaured video of the content
    * @return object
    */
    public function featuredVideo()
    {
        return $this->hasOne(Media::class, 'id', 'featured_video_id');
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
    * Return the relationship to the categories to which the content belongs
    * @return object
    */
    public function related()
    {
        return $this->belongsToMany($this->getTranslationRelationTarget(), 'content_related_content', 'content_id', 'related_content_id')
        ->withPivot('related_content_type');
    }

    /**
     * Get the content url
     *
     * @return string
     */
    public function url(): string {
        // the root url has a special tretment, to avoid having double slashes
        $section_url = $this->section->url === "/"? "" : $this->section->url;

        // if the section in question is not the default, the locale is prepended to the section url
        if ($this->section->locale !== env("DEFAULT_LOCALE")) {
            $section_url = "/".strtolower($this->section->locale).$section_url;
        }

        $contentTypeTranslated = trans('content-type.'.$this->content_type, [], $this->section->locale);

        // build the content url
        return "$section_url/$contentTypeTranslated/$this->slug/$this->id";
    }

    /**
     * Get the urlof all content locale version
     * This method must be used with caution, because consumes a lot of resources!
     *
     * @return array
     */
    public function urls(): array {
        $urls = [];

        // get raw data from db to avoid the overrad of loading the entire model with all relations
        $rawContents = DB::table($this->getTable())
            ->join('sections', 'sections.id', '=', 'contents.section_id')
            ->where('contents.multi_lang_content_id', $this->multi_lang_content_id)
            ->get();

        // for each content, build the url
        foreach ($rawContents as $rawContent) {

            // translate the content type to be used in the url
            $contentTypeTranslated = trans('content-type.'.$rawContent->content_type, [], $rawContent->locale);

            // the root url has a special tretment, to avoid having double slashes
            $section_url = $rawContent->url === "/"? "" : $rawContent->url;

            // if the section in question is not the default, the locale is prepended to the section url
            if ($rawContent->locale !== env("DEFAULT_LOCALE")) {
                $section_url = "/".strtolower($rawContent->locale).$section_url;
            }
            $urls[$rawContent->locale] = "$section_url/$contentTypeTranslated/$rawContent->slug/$rawContent->id";
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
            "sectionTitle" => $data["section"]["title"],
            "slug" => $data["slug"],
            "section_id" => $data["section_id"],
            "content_id" => $data["id"]
        ];

        $data["urls"] = $this->urls();
        $data["url"] = $data["urls"][$data["locale"]];

        $data["statusLabel"] = ContentStatus::translation($data["status"]);
        return $data;
    }
}
