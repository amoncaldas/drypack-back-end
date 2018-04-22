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
use \App;


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
     * Apply filters to translations as conditions to get multilanguage content
     *
     * @param Request $request
     * @param Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    protected function applyWhereTranslationHasFilters($request, $query) {
        if ($request->has('locale')) {
            $query = $query->where('locale', $request->locale);
        }

        $user = $this->getUser();
        $query = $query->whereHas('users', function ($q) use ($user) {
            $q->where('id', $user->id);
        });
    }

    /**
     * Apply with/join rules and filters to translations as conditions to get multilanguage content
     *
     * @param Request $request
     * @param Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    protected function applyWithTranslationRules($request, $query) {
        $query = $query->with('users');

        // if it is not the admin environment, we only get the current language translation
        // to avoid the overhead
        if(!$this->isAdmin()) {
            $query->where('locale', App::getLocale());
        }
    }

    /**
     * Before save, validate unique fields
     *
     * @param Request $request
     * @param Model $content
     * @return void
     */
    protected function beforeSave(Request $request, Model $content) {
        parent::beforeSave($request, $content);

        foreach ($request->input('translations') as $key => $value) {
            $exists = Section::where('locale', $value['locale'])
                ->where('multi_lang_content_id', '<>', $request->id)
                ->where('url', $value['url'])->exists();

            if($exists) {
                $this->validator->errors()->add('unique',
                    Lang::get('validation.unique_name_and_slug_in_all_locales',
                    ['resources' => Lang::get('validation.attributes.sections')])
                );
                $this->throwValidationException($request, $this->validator);
            }
        }
    }

    /**
     * Callback used to save additional translation specifique data.
     * After save is only fired if the beforeSaveTranslation returns nothing|null|true
     *
     * @return void
     */
    protected function afterSaveTranslation(Request $request, Model $translation, $trans_arr) {
        if(isset($trans_arr['users'])) {
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
        $validations = [
            'translations' => 'required|array|min:1',
            'translations.*.locale'=>'required',
            'translations.*.title'=>'required',
            'translations.*.url'=>'required'
        ];

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
            "unique"=> Lang::get('validation.unique_name_and_slug_in_all_locales', ['resources' => Lang::get('validation.attributes.sections')])
        ];
    }
}
