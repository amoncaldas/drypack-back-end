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
use App\Content\Content;
use Lang;
use App\Content\ContentStatus;
use \Auth;
use \App;
use OwenIt\Auditing\Models\Audit;


abstract class ContentController extends BaseMultiLangContentController
{
    /**
     * Apply filters to translations as conditions to get multilanguage content
     *
     * @param Request $request
     * @param Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    protected function applyWhereTranslationHasFilters($request, $query) {
        if ($request->has('title')) {
            $query->where('title', 'ilike', '%'.$request->title.'%');
        }
        if ($request->has('idNotIn') && $request->idNotIn  !== null) {
            $query->whereNotIn('id', explode(',', $request->idNotIn));
        }
        if ($request->has('locale')) {
            $query->where('locale', $request->locale);
        }

        $user = $this->getUser();
        if ($user !== null && $this->isAdmin()) {
            // Get an array with user's section
            $user_sections_id = $user->sections()->get()->pluck('id')->all();
            $query = $query->whereHas('section',  function ($sectionQuery) use($user_sections_id) {
                $sectionQuery->whereIn('id', $user_sections_id);
            });
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
        $query = $query->with('categories')->with('authors')->with('section');

        // if it is not the admin environment, we only get the current language translation
        // to avoid the overhead
        if(!$this->isAdmin()) {
            $query = $query->where('locale', App::getLocale());
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
        $klass = $this->getTranslationModel();

        foreach ($request->input('translations') as $key => $value) {
            $exists = $klass::where('locale', $value['locale'])
                ->where('multi_lang_content_id', '<>', $request->id)
                ->where('content_type', $this->getContentType())
                ->where(function($q) use($value) {
                    $q->where('title', $value['title'])->orWhere('slug', $value["url_segments"]['slug']);
                })
                ->where('slug', $value["url_segments"]["section_id"])
                ->exists();

            if($exists) {
                $this->validator->errors()->add('unique',
                    Lang::get('validation.unique_name_and_slug_in_all_locales',
                    ['resources' => Lang::get('validation.attributes.contents')])
                );
                $this->throwValidationException($request, $this->validator);
            }
        }
    }

    /**
     * Return the content validation rules
     *
     * @param Request $request
     * @param Model $obj
     * @return array $validations rules
     */
    protected function getValidationRules(Request $request, Model $content)
    {
        $user_sections_id = Auth::user()->sections()->get()->pluck('id')->all();

        $validations = [
            'translations' => 'required|array|min:1',
            'translations.*.locale'=>'required',
            'translations.*.title'=>'required|min:3',
            'translations.*.url_segments' => 'required|array|min:2',
            'translations.*.url_segments.slug'=>'required|min:3',
            'translations.*.url_segments.section_id'=>'required|integer',
            'translations.*.url_segments.section_id'=>'required|integer|in:'.implode(',', $user_sections_id),
            'translations.*.status'=>'required|in:'.implode(',', ContentStatus::allKeys())
        ];

        if($request->has('translations')) {
            foreach ($request->translations as $key => $value) {
                if($value["status"] === ContentStatus::$passwordProtected["slug"]) {
                    $validations['translations.*.password'] = 'required';
                }
                elseif($value["status"] === ContentStatus::$published["slug"]) {
                    $validations['translations.*.content'] = 'required|min:100';
                    $validations['translations.*.abstract'] = 'required|min:100';
                    $validations['translations.*.short_desc'] = 'required|min:100';
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
    protected function messages($request) {
        $messages = [
            "unique"=> Lang::get('validation.unique_title_and_slug_in_all_locales', ['resources' => Lang::get('validation.attributes.'.$this->getContentType())]),
            "translations.required" => Lang::get('validation.at_least_one_translation_required'),
            "translations.array" => Lang::get('validation.at_least_one_translation_required'),
            "translations.min" => Lang::get('validation.at_least_one_translation_required'),
            "translations.*.url_segments.required" => Lang::get('validation.at_least_one_translation_required'),
            "translations.*.url_segments.array" => Lang::get('validation.at_least_one_translation_required'),
            "translations.*.url_segments.min" => Lang::get('validation.at_least_one_translation_required'),
            "translations.*.url_segments.slug.required" => Lang::get('validation.field_required_in_all_locales', ['field' => 'url']),
            "translations.*.url_segments.section_id.required" => Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.section')]),
            "translations.*.url_segments.section_id.in" => Lang::get('validation.field_in', ['field' => 'id']),
            "translations.*.title.required" => Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.title')]),
            "translations.*.locale.required" => Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.locale')]),
            "translations.*.status.in" => Lang::get('validation.field_in', ['field' => Lang::get('validation.attributes.status'), 'in'=> implode(',', ContentStatus::allTrans())]),
            "translations.*.status.required" => Lang::get('validation.field_required_in_all_locales', ['field' => 'status'])
        ];

        foreach ($request->translations as $key => $value) {
            if($value["status"] !== ContentStatus::$draft["slug"]) {
                $messages["translations.$key.content.required"] = Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.content')]);
                $messages["translations.$key.content.min"] = Lang::get('validation.field_min_in_all_locales', ['field' => Lang::get('validation.attributes.content'), 'min'=>100]);
                $messages["translations.$key.abstract.required"] = Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.abstract')]);
                $messages["translations.$key.abstract.min"] = Lang::get('validation.field_min_in_all_locales', ['field' => Lang::get('validation.attributes.abstract'), 'min'=>100]);
                $messages["translations.$key.short_desc.required"] = Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.short_desc')]);
                $messages["translations.$key.short_desc.min"] = Lang::get('validation.field_min_in_all_locales', ['field' => Lang::get('validation.attributes.short_desc'), 'min'=>100]);
                // $messages['translations.*.featured_image_id.required'] = Lang::get('validation.field_required_in_all_locales', ['field' => Lang::get('validation.attributes.featured_image_id')]);
            }
            if($value["status"] === ContentStatus::$passwordProtected["slug"]) {
                $messages["translations.$key.password.required"] = Lang::get('validation.password_required');
            }
        }

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
        if(isset($trans_arr['related'])) {
            $translation->related()->detach();
            $ids = array_pluck($trans_arr['related'], 'id');
            foreach ($trans_arr['related'] as $related) {
                $klass = $this->getTranslationModel();
                $relatedModel = $klass::find($related["id"]);
                if (isset($relatedModel)) {
                    $translation->related()->attach($related["id"], ['related_content_type'=>$relatedModel->content_type]);
                }
            }
        }
    }

    /**
     * Callback used to modify the translation model before save
     *
     * @return void|boolean if return false the auto save in parent is canceled
     */
    protected function beforeSaveTranslation(Request $request, Model $content, $content_arr) {
        $content->content_type = $this->getContentType();
        $this->validateAndSetStatus($request, $content, $content_arr);

        // set the published at date, if it is being saved as published
        if($content_arr["status"] === ContentStatus::$published["slug"] && isset($content_arr["published_at"])) {
            $content->published_at = \DryPack::parseDate($content_arr["published_at"]);
        }

        // hash the password, if it is being saved as password protected
        if($content_arr["status"] === ContentStatus::$passwordProtected["slug"] && isset($content_arr["password"])) {
            $content->password = bcrypt($content_arr["password"]);
        }

        $content->slug = $content_arr["url_segments"]["slug"];
        $content->section_id = $content_arr["url_segments"]["section_id"];

        // This code snippet allow the user specify an id for a new content
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

    /**
     * Validate and set the content status
     *
     * @param Request $request
     * @param Model $content
     * @return void
     */
    protected function validateAndSetStatus(Request $request, Model $content, $content_arr) {

        $content->status = ContentStatus::$draft["slug"];

        if(isset($content_arr['status'])) {
            $action = ContentStatus::getStatusAction($content_arr['status']);
            if (Auth::user()->hasResourcePermission($this->getContentType(), $action)) {
                $content->status = $content_arr['status'];
            } else {
                $request->merge(["warning"=>'the_status_was_set_as_draft_because_of_lack_of_permission']);
            }
        }
    }

    /**
     * Return the content revisions id, date and its author name
     *
     * @param Request $request
     * @param integer $contentId
     * @return void
     */
    public function revisions(Request $request, $contentId ) {
        $type = $this->getTranslationRelationTarget();

        $revisions = Audit::with(['user' => function($query) {
            $query->select('id','name');
        }])
        ->select('id', 'created_at', 'user_id')
        ->where('auditable_type', $type)
        ->where('auditable_id', $contentId)
        ->whereIn('event', ["updated", "created"])
        ->orderBy('created_at', 'desc')
        ->get();

        $data['items'] = $revisions;
        $data['total'] = count($data['items']);

        return $data;
    }

    /**
     * Get the full data of a content specifique revision
     *
     * @param Request $request
     * @param integer $contentId
     * @param integer $revisionId
     * @return void
     */
    public function revision(Request $request, $contentId, $revisionId) {
        $revision = Audit::find($revisionId);
        return $revision;
    }


}
