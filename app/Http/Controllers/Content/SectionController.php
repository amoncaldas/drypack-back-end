<?php

namespace App\Http\Controllers\Content;

use App\Content\MultiLangContent;
use App\Content\Section;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Content\BaseMultiLangContentController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;


class SectionController extends BaseMultiLangContentController
{
    /**
     * Get the target relation class with namespace
     *
     * @return string
     */
    public function getTranslationRelationTarget()
    {
        return "App\Content\Section";
    }

    /**
     * Get the translation model class.
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getTranslationModel()
    {
        return Section::class;
    }

    /**
     * Get the content type of the translation class
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getContentType(){
        return "section";
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
            'translations.*.title'=>'required',
            'translations.*.url'=>'required'
        ];

        if($request->has('translations')) {
            foreach ($request->input('translations') as $key => $value) {
                // Append the id in the unique validation to avoid failing in the update validation
                $uniqueFilterAppend = isset($value["id"])?  ','.$value["id"] : '';
                $validations["translations.$key.title"] = "unique:sections,title".$uniqueFilterAppend;
                $validations["translations.$key.url"] = "unique:sections,url".$uniqueFilterAppend;
            }
        }

        return $validations;
    }
}
