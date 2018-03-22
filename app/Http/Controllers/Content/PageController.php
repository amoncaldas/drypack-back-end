<?php

namespace App\Http\Controllers\Content;

use App\Content\MultiLangContent;
use App\Content\Page;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Content\BaseMultiLangContentController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;


class PageController extends BaseMultiLangContentController
{
    /**
     * Get the target relation class with namespace
     *
     * @return string
     */
    public function getTranslationRelationTarget()
    {
        return "App\Content\Page";
    }

    /**
     * Get the translation model class.
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getTranslationModel()
    {
        return Page::class;
    }

    /**
     * Get the content type of the translation class
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getContentType(){
        return "page";
    }

    /**
     * Run the parent applyFilters and add the filters
     *
     * @param Request $request
     * @param [type] $query
     * @return void
     */
    protected function applyFilters(Request $request, $query) {
        parent::applyFilters($request, $query);
        if ($request->has('locale')) {
            $query = $query->whereHas('translations', function ($query) use($request) {
                $query->where('locale', $request->locale);
            });
        }
    }

     /**
     * Return the role validation rule
     *
     * @param Request $request
     * @param Model $obj
     * @return void
     */
    protected function getValidationRules(Request $request, Model $obj)
    {
        //TODO: add custom messages to each validation

        $validations = [
            'translations' => 'required|array|min:1',
            'translations.*.locale'=>'required',
            'translations.*.title'=>'required|min:3',
            'translations.*.url' => 'required|array|min:2',
            'translations.*.url.slug'=>'required|min:3',
            'translations.*.url.section_id'=>'required|integer'
        ];

        if($request->has('translations')) {
            foreach ($request->input('translations') as $key => $value) {
                // Append the id in the unique validation to avoid failing in the update validation
                $uniqueFilterAppend = isset($value["id"])?  ','.$value["id"] : '';

                //TODO: add unique considering the section_id
                $validations["translations.$key.title"] = "unique:pages,title".$uniqueFilterAppend;

                //TODO: add unique considering the section_id
                $validations["translations.$key.url.slug"] = "unique:pages,slug".$uniqueFilterAppend;
            }
            if($request->status === "published") {
                $validations['translations.*.content'] = 'required|min:100';
                $validations['translations.*.abstract'] = 'required|min:100';
                $validations['translations.*.short_desc'] = 'required|min:100';
                $validations['translations.*.featured_image_id'] = 'required|integer';
            }
        }

        return $validations;
    }

    /**
     * Callback used to save additional translation specifique data
     *
     * @return void
     */
    protected function afterSaveTranslation(Request $request, Model $translation) {

    }

    /**
     * Callback used to modify the translation model before save
     *
     * @return void
     */
    protected function beforeSaveTranslation(Request $request, Model $translation, $trans_arr) {
        if(!$request->has('status')) {
            $translation->status = "draft";
        }

        $translation->slug = $trans_arr["url"]["slug"];
        $translation->section_id = $trans_arr["url"]["section_id"];
    }


}
