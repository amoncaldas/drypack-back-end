<?php

namespace App\Http\Controllers\Content;

use App\Content\MultiLangContent;
use App\Content\Post;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Content\ContentController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Input;
use App\User;
use App\Content\Category;


class PostController extends ContentController
{
    /**
     * * Get additional fields to be added in the selected in the index action
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
    public function getContentType(){
        return "post";
    }

    /**
     * Get the target relation class with namespace
     *
     * @return string
     */
    public function getTranslationRelationTarget()
    {
        return "App\Content\Post";
    }

    /**
     * Get the translation model class.
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function getTranslationModel()
    {
        return Post::class;
    }
}
