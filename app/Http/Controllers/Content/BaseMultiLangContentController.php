<?php

namespace App\Http\Controllers\Content;

use App\Content\MultiLangContent;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\CrudController;
use Illuminate\Database\Eloquent\Model;
use \Auth;
use \App\Exceptions\BusinessException;
use App\Authorization\Authorization;
use \App;

abstract class BaseMultiLangContentController extends CrudController
{
    /**
     * Get the target relation class with namespace, like "App\MyClass"
     *
     * @return string
     */
    abstract protected function getTranslationRelationTarget();

    /**
     * Get the translation model class.
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    abstract protected function getTranslationModel();

    /**
     * Get the content type of the translation class
     *
     * @return string
     */
    abstract protected function getContentType();


    /**
     * Return the model to be used to build the base query
     *
     * @return void
     */
    protected function getModel()
    {
        return MultiLangContent::class;
    }

    /**
     * Set the translation relation target class with namespace
     * add the translations and author withs and the type filter
     *
     * @param Request $request
     * @param Illuminate\Database\Eloquent\Builder
     * @return void
     */
    protected function applyFilters(Request $request, $query) {
        $this->setTranslations($request, $query);

        $query->whereHas('translations', function ($query) use ($request) {
            $query->where('type', $this->getContentType());

            if (method_exists($this, "applyWhereTranslationHasFilters" )) {
                $this->applyWhereTranslationHasFilters($request, $query);
            }
        });

        // if the user has not the permission to index other users' content
        // and the content has this type of action as an assignable permission
        // limit the contents to the ones that belongs to the current user
        $user = $this->getUser();
        $resourceActions = Authorization::getResourceActions($this->getContentType());
        if(isset($resourceActions["index_others"]) && $user->hasResourcePermission($this->getContentType(), "index_others")) {
            $query->where('owner_id', Auth::user()->id);
        }
    }

    /**
     * Set the translations relations and rules to be used to get a single instance
     *
     * @param Request $request
     * @param Illuminate\Database\Eloquent\Builder $query
     * @param integer $id
     * @return void
     */
    protected function beforeShow(Request $request, $query, $id) {
        $this->setTranslations($request, $query, true);
    }

    /**
     * Set the translations relations and rules to be used
     *
     * @param Request $request
     * @param Illuminate\Database\Eloquent\Builder $query
     * @param boolean $allFields
     * @return void
     */
    protected function setTranslations(Request $request, $query, $allFields = null) {
        // Initialize the optional $allFields parameter
        $allFields = $allFields ? $allFields : false;

        $model = $query->getModel();
        $model->setTranslationRelationTarget($this->getTranslationRelationTarget());
        $query->setModel($model);
        $query = $query->with("owner");

        $query = $query->with(['translations' => function ($query) use ($request, $allFields) {
            if (method_exists($this, "applyWithTranslationRules" )) {
                $this->applyWithTranslationRules($request, $query, $allFields);
            }
        }]);
    }


    /**
     * After saving the MultiLangContent, save the target translations content linked to the content
     *
     * @param Request $request
     * @param Model $content
     * @return void
     */
    protected function afterSave(Request $request, Model $content) {
        $translations = $request->input('translations');
        if($translations && count($translations) > 0){
            foreach ($translations as $trans_arr) {
                $trans_arr['multi_lang_content_id'] = $content->id;
                $this->saveTranslation($request, $trans_arr);
            }
        }
    }

    /**
     * Save a translation
     *
     * @param Request $request
     * @param array $trans_arr
     * @return void
     */
    protected function saveTranslation(Request $request, array $trans_arr) {
        $klass = $this->getTranslationModel();

        if(isset($trans_arr["id"])) { // it is an update
            $translation = $klass::find($trans_arr["id"]);
            $translation->fill($trans_arr);
        } else { // it is a create new
            $translation = new $klass($trans_arr);
        }
        $proceedSave = true; // the default behavior is to proceed

        // the derived controller class can cancel the auto save if it returns false
        if (method_exists($this, "beforeSaveTranslation" )) {
            $proceedSave = $this->beforeSaveTranslation($request, $translation, $trans_arr);
        }
        // if proceedSave is true or was not returned (is null), the auto save is proceeded
        if($proceedSave === true || $proceedSave === null) {
            $translation->save();
            if (method_exists($this, "afterSaveTranslation" )) {
                $this->afterSaveTranslation($request, $translation, $trans_arr);
            }
        }
    }

    /**
     * Set the target relation class with namespace and remove the old translations,
     * so we store the updated ones
     *
     * @param Request $request
     * @param Model $content
     * @return void
     */
    protected function beforeUpdate(Request $request, Model $content) {
        $user = $this->getUser();
        $resourceActions = Authorization::getResourceActions($this->getContentType());

        if($content->owner_id !== $user->id &&
            isset($resourceActions["update_others"]) &&
            !$user->hasResourcePermission($this->getContentType(), "update_others")) {
                throw new BusinessException("you_dont_have_permission_to_update_this_content");
        }
        $content->setTranslationRelationTarget($this->getTranslationRelationTarget());
    }

    /**
     * Check if the curent user can destroy/delete the content. In not, raise an BusinessException
     *
     * @param Request $request
     * @param Model $content
     * @return void
     */
    protected function beforeDestroy(Request $request, Model $content) {
        $user = $this->getUser();
        $resourceActions = Authorization::getResourceActions($this->getContentType());

        if($content->owner_id !== $user->id &&
            isset($resourceActions["destroy_others"]) &&
            !$user->hasResourcePermission($this->getContentType(), "destroy_others")) {
                throw new BusinessException("you_dont_have_permission_to_destroy_this_content");
        }
    }


    /**
     * When a multi lang content is freshed it is needed to set the target translation relation
     * define din the parent controller
     *
     * @param Request $request
     * @param Model $content
     * @return void
     */
    protected function afterFresh(Request $request, Model $content) {
        $content->setTranslationRelationTarget($this->getTranslationRelationTarget());
    }


    /**
     * Before save, set the content type and the author id
     *
     * @param Request $request
     * @param Model $content
     * @return void
     */
    protected function beforeSave(Request $request, Model $content) {
        $content->type = $this->getContentType();
        $user = $this->getUser();
        $content->owner_id = $user->id;
        $resourceActions = Authorization::getResourceActions($this->getContentType());

        if($request->has('owner_id')) {
            // if the update owner is not monitored or is monitored and the current user has this permission, update it
            if (!isset($resourceActions["update_owner"]) || $user->hasResourcePermission($this->getContentType(), "update_owner")) {
                $content->owner_id = $request->owner_id;
            }
        }
    }
}
