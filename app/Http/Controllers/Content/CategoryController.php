<?php

namespace App\Http\Controllers\Content;

use App\Content\Category;
use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;
use App\Util\DryPack;
use App\Http\Controllers\Content\BaseMultiLangContentController;
use Lang;
use \App;


class CategoryController extends BaseMultiLangContentController
{
    public function __construct()
    {
    }

    /**
     * Get the target relation class with namespace
     *
     * @return string
     */
    public function getTranslationRelationTarget()
    {
        return "App\Content\Category";
    }

    /**
     * Get the translation model class.
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getTranslationModel()
    {
        return Category::class;
    }

    /**
     * Get the content type of the translation class
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getContentType(){
        return "category";
    }

    /**
     * Return the category validation rules
     *
     * @param Request $request
     * @param Model $obj
     * @return void
     */
    protected function getValidationRules(Request $request, Model $obj)
    {
        $validations = [
            'translations' => 'required|array|min:1',
            'translations.*.locale'=>'required',
            'translations.*.label'=>'required',
            'translations.*.slug'=>'required'
        ];

        if($request->has('translations')) {
            foreach ($request->input('translations') as $key => $value) {
                // Append the id in the unique validation to avoid failing in the update validation
                $uniqueFilterAppend = isset($value["id"])?  ','.$value["id"] : '';
                $validations["translations.$key.label"] = "unique:categories,label".$uniqueFilterAppend;
                $validations["translations.$key.slug"] = "unique:categories,slug".$uniqueFilterAppend;

                if($key > 0) {
                    $validations["translations.$key.parent_multi_lang_content_id"] = "same:translations.0.parent_multi_lang_content_id";
                }
            }
        }

        return $validations;
    }

    /**
     * Specify custom messages for the validation
     *
     * @return void
     */
    protected function messages() {
        $categories = Lang::get('validation.attributes.categories');
        return [
            "same"=> Lang::get('validation.same_parent', ['resources' => $categories]),
            "required"=> Lang::get('validation.all_required_in_all_locales', ['item' => Lang::get('validation.attributes.category')]),
            "unique"=> Lang::get('validation.unique_name_and_slug_in_all_locales', ['resources' => $categories])
        ];
    }

    /**
     * Apply filters to translations as conditions to get multilanguage content
     *
     * @param Request $request
     * @param Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    protected function applyWhereTranslationHasFilters($request, $query) {
        // $query = $query->where('locale', $request->locale);

        if(!$this->isAdmin()) {
            $query = $query->where('locale', App::getLocale());
        }

        if($request->has('label')) {
            $query = $query->where('label', 'ilike', '%'.$request->label.'%');
        }

        if($request->has('notIn')) {
            $notIn = is_array($request->notIn)? $request->notIn : [$request->notIn];
            $query = $query->whereNotIn('id', $notIn);
        }
    }

    /**
     * Apply with/join rules and filters to translations as conditions to get multilanguage content
     *
     * @param Request $request
     * @param Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    protected function applyWithTranslationRules($request, $query) {
        $query = $query->with('parentCategory');

        // if it is not the admin environment, we only get the current language translation
        // to avoid the overhead
        if(!$this->isAdmin()) {
            $query = $query->where('locale', App::getLocale());
        }
    }

    /**
     * Define the category parent id based on the parent category multilanguage id
     *
     * @param Request $request
     * @param Model $category
     * @param array $trans_arr
     * @return void
     */
    protected function beforeSaveTranslation(Request $request, Model $category, $trans_arr) {
        if(!isset($category->slug)) {
            $category->slug = DryPack::getSlug($category->label);
        }

        // The parent multilanguage content id is sent, instead of the parent category id
        // because it can be used during validation time to determine if the multiple locale
        // versions of a category has as parent the "same" category even if they are
        // different locale versions of the same category
        // we get the target parent category based on the category parent multilanguage content id
        if (isset($trans_arr["parent_multi_lang_content_id"])) {
            $category->parent_category_id = Category::where("multi_lang_content_id", $trans_arr["parent_multi_lang_content_id"])
                ->where("locale", $trans_arr["locale"])->first()->id;
        }
    }

}
