<?php

namespace App\Http\Controllers\Content;

use App\Content\MultiLangContent;
use App\Content\Page;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Content\BaseMultiLangContentController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;
use App\User;
use App\Content\Category;
use Lang;


abstract class ContentController extends BaseMultiLangContentController
{

    /**
     * Run the parent applyFilters and add the filters
     *
     * @param Request $request
     * @param [type] $query
     * @return void
     */
    protected function applyFilters(Request $request, $query) {
        parent::applyFilters($request, $query);

        $query = $query->with(['translations' => function ($query) use($request) {
            $query->with('categories')->with('authors')->with('section');
        }]);

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
            'translations.*.url_segments' => 'required|array|min:2',
            'translations.*.url_segments.slug'=>'required|min:3',
            'translations.*.url_segments.section_id'=>'required|integer'
        ];

        if($request->has('translations')) {
            foreach ($request->input('translations') as $key => $value) {
                // Append the id in the unique validation to avoid failing in the update validation
                $uniqueFilterAppend = isset($value["id"])?  ','.$value["id"] : '';

                //TODO: add unique considering the section_id
                $validations["translations.$key.title"] = "unique:contents,title".$uniqueFilterAppend;
                //TODO: add unique considering the section_id
                $validations["translations.$key.url.slug"] = "unique:contents,slug".$uniqueFilterAppend;
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
     * Specify custom messages for the validation
     *
     * @return void
     */
    protected function messages($request) {
        $messages = [
            "unique"=> Lang::get('validation.unique_title_and_slug_in_all_locale', ['resources' => Lang::get('validation.attributes.'.$this->getContentType())]),
            "translations.required" => Lang::get('validation.at_least_one_translation_required'),
            "translations.array" => Lang::get('validation.at_least_one_translation_required'),
            "translations.min" => Lang::get('validation.at_least_one_translation_required'),
            "translations.*.url_segments.required" => Lang::get('validation.at_least_one_translation_required'),
            "translations.*.url_segments.array" => Lang::get('validation.at_least_one_translation_required'),
            "translations.*.url_segments.min" => Lang::get('validation.at_least_one_translation_required'),
            "translations.*.url_segments.slug.required" => Lang::get('validation.field_required_in_all_locales', ['field' => 'url']),
            "translations.*.url_segments.section_id.required" => Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.section')]),
            "translations.*.title.required" => Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.title')]),
            "translations.*.locale.required" => Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.locale')])
        ];

        if($request->status === "published") {
            $messages['translations.*.content.required'] = Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.content')]);
            $messages['translations.*.content.min'] = Lang::get('validation.field_min_in_all_locales', ['field' => Lang::get('validation.attributes.content'), 'min'=>100]);
            $messages['translations.*.abstract.required'] = Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.abstract')]);
            $messages['translations.*.abstract.min'] = Lang::get('validation.field_min_in_all_locales', ['field' => Lang::get('validation.attributes.abstract'), 'min'=>100]);
            $messages['translations.*.short_desc.required'] = Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.short_desc')]);
            $messages['translations.*.short_desc.min'] = Lang::get('validation.field_min_in_all_locales', ['field' => Lang::get('validation.attributes.short_desc'), 'min'=>100]);
            $messages['translations.*.featured_image_id.required'] = Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.featured_image_id')]);
        }

        // foreach ($request->input('translations') as $key => $value) {
        //     $messages["translations.$key.url_segments.slug.required"] = Lang::get('validation.field_required_in_all_locales', ['field' => 'url']);
        //     $messages["translations.$key.url_segments.section_id.required"] = Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.section')]);
        //     $messages["translations.$key.title.required"] = Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.title')]);
        //     $messages["translations.$key.locale.required"] = Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.locale')]);
        // }
        return $messages;
    }

    /**
     * Callback used to save additional translation specifique data.
     * After save is only fired if the beforeSaveTranslation returns nothing|null|true
     *
     * @return void
     */
    protected function afterSaveTranslation(Request $request, Model $translation, $trans_arr) {
        if(isset($trans_arr['authors'])) {
            $translation->authors()->detach();
            $ids = array_pluck($trans_arr['authors'], 'id');
            $translation->authors()->attach($ids, ['content_type'=>$this->getContentType()]);
        }
        if(isset($trans_arr['categories'])) {
            $translation->categories()->detach();
            $ids = array_pluck($trans_arr['categories'], 'id');
            $translation->categories()->attach($ids, ['content_type'=>$this->getContentType()]);
        }
    }

    /**
     * Callback used to modify the translation model before save     *
     *
     * @return void|boolean if return false the auto save in parent is canceled
     */
    protected function beforeSaveTranslation(Request $request, Model $content, $content_arr) {
        $content->content_type = $this->getContentType();
        if(!$request->has('status')) {
            $content->status = "draft";
        }

        $content->slug = $content_arr["url_segments"]["slug"];
        $content->section_id = $content_arr["url_segments"]["section_id"];

        // this code snippet allow the user specify an id for a new content
        // this is necessary when the user is migrating content from other system
        // and wants to keep the same friendly url, including the same id
        if( isset($content_arr["url_segments"]["content_id"])) {
            $id = $content_arr["url_segments"]["content_id"];
            $existingContentWithSameId = Page::find($id);
            // this is allowed only if a content with the specified id does not already exist
            if ($existingContentWithSameId === null) {
                $content->id =  $id;
            }
        }

        // return false to cancel auto save and then save by yourself in your code here
        return true;
    }


}
