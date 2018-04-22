<?php

namespace App\Content;

use App\BaseModel;

use Illuminate\Database\Eloquent\Model;
use App\Content\MultiLangContent;

class Category extends BaseModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'categories';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['label', 'slug', 'locale','multi_lang_content_id', 'parent_category_id'];


    /**
    * Return the relationship to the project to which the the task belongs to
    * @return object
    */
    public function multiLangContent()
    {
        return $this->belongsTo(MultiLangContent::class);
    }

    /**
    * Get the parent categories
    * @return object
    */
    public function parentCategory()
    {
        return $this->belongsTo(Category::class, 'parent_category_id', 'id');
    }

    // override the toArray function (called by toJson)
    public function toArray() {
        // Get the original array to be displayed
        $data = parent::toArray();

        // Add relation data
        $data['relations_count'] = \DB::table('category_content')->where('category_id', '=', $this->id)->count();
        if (isset($data["parent_category_id"])) {
            // TODO: add explanation!
            $data['parent_multi_lang_content_id'] = Category::where("id", $data["parent_category_id"])->first()->multi_lang_content_id;
        }
        return $data;
    }
}



