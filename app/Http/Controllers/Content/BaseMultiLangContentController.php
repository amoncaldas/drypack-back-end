<?php

namespace App\Http\Controllers\Content;

use App\Content\MultiLangContent;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\CrudController;
use Illuminate\Database\Eloquent\Model;


abstract class BaseMultiLangContentController extends CrudController
{
    /**
     * Get the target relation class with namespace, like "App\MyClass"
     *
     * @return string
     */
    abstract public function getTranslationRelationTarget();

    /**
     * Get the translation model class.
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    abstract public function getTranslationModel();

    /**
     * Get the content type of the translation class
     *
     * @return string
     */
    abstract public function getContentType();


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
        $model = $query->getModel();
        $model->setTranslationRelationTarget($this->getTranslationRelationTarget());
        $query->setModel($model);
        $query = $query->with("translations")->with("author")->where("type",$this->getContentType());
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
                $klass = $this->getTranslationModel();
                $translation = new $klass($trans_arr);
                $proceedSave = true;

                // the derived controller class can cancel the auto save if it returns false
                if (method_exists($this, "beforeSaveTranslation" )) {
                    $proceedSave = $this->beforeSaveTranslation($request, $translation, $trans_arr);
                }
                // if proceedSave is true or was not returned (is null), the auto save is proceeded
                if($proceedSave === true || $proceedSave === null) {
                    $translation->save();
                    if (method_exists($this, "afterSaveTranslation" )) {
                        $this->afterSaveTranslation($request, $translation);
                    }
                }
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
        $content->setTranslationRelationTarget($this->getTranslationRelationTarget());
        $content->translations()->delete();
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
        $content->owner_id = \Auth::user()->id;
    }
}
