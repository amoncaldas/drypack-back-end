<?php

namespace App\Http\Controllers\Content;

use App\Content\MultiLangContent;
use App\Content\Page;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Content\ContentController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;
use App\User;
use App\Content\Category;


class PageController extends ContentController
{
    /**
     * Get additional fields to be added in the selected in the index action
     *
     * @return array
     */
    protected function getListAttrs() {
        return ['title'];
    }

    /**
     * Get the content type of the translation class
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    protected function getContentType(){
        return "page";
    }

    /**
     * Get the target relation class with namespace
     *
     * @return string
     */
    protected function getTranslationRelationTarget()
    {
        return "App\Content\Page";
    }

    /**
     * Get the translation model class.
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    protected function getTranslationModel()
    {
        return Page::class;
    }

    /**
     * Return the content validation rules
     *
     * @param Request $request
     * @param Model $content
     * @return array $validations rules
     */
    protected function getValidationRules(Request $request, Model $content)
    {
        // Get the parent full validation list
        $validations = parent::getValidationRules($request, $content);

        // check if there are translations on the request
        if($request->has('translations')) {
            foreach ($request->translations as $key => $value) {
                // remove the abstract request, as it is not necessary in pages
                unset($validations["translations.*.abstract"]);
            }
        }

        return $validations;
    }
}
