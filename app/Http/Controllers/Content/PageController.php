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
     * Get the content type of the translation class
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getContentType(){
        return "page";
    }

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
}
