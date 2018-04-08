<?php

namespace App\Http\Controllers\Content;

use App\Content\MultiLangContent;
use App\Content\Section;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Content\BaseMultiLangContentController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;
use \Auth;
use Lang;


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

        $query = $query->with(['translations' => function ($query) use($request) {
            $query->with('users');
        }]);

        // local function to add current user as a filter
        $filterByUser = function($query) {
            // if the current user is not the first admin,
            // only search for section which the curretn user is linked to
            if(!Auth::user()->isFirstAdmin()) {
                $query = $query->whereHas('users', function ($query) {
                    $query->where('id', Auth::user()->id);
                });
            }
        };
        // if a locale was passed, add it as a filter and also the current user
        if ($request->has('locale')) {
            $query = $query->whereHas('translations', function ($query) use($request, $filterByUser) {
                $query->where('locale', $request->locale);
                $filterByUser($query);
            });
        }
        // if not, add only the current user as a filter
        else {
            $query = $query->whereHas('translations', function ($query) use($filterByUser) {
                $query = $filterByUser($query);
            });
        }
    }

    /**
     * Callback used to save additional translation specifique data.
     * After save is only fired if the beforeSaveTranslation returns nothing|null|true
     *
     * @return void
     */
    protected function afterSaveTranslation(Request $request, Model $translation, $trans_arr) {
        if(isset($trans_arr['users']) && count($trans_arr['users']) > 0) {
            $translation->users()->detach();
            $ids = array_pluck($trans_arr['users'], 'id');
            $translation->users()->attach($ids);
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

     /**
     * Specify custom messages for the validation
     *
     * @return void
     */
    protected function messages() {
        return [
            "required"=> Lang::get('validation.all_required_in_all_locales', ['item' => Lang::get('validation.attributes.section')]),
            "unique"=> Lang::get('validation.unique_name_and_slug_in_all_locale', ['resources' => Lang::get('validation.attributes.sections')])
        ];
    }
}
