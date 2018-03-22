<?php

namespace App\Content;

use App\Content\Content;

class Page extends Content
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = "pages";

    protected function getContentType() {
        return "page";
    }

}
