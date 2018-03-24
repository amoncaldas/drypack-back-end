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

     /**
     * Override the base toArray method to include custom attributes
     *
     * @return array with model's data
     */
    public function toArray() {
        $data = parent::toArray();
        $data["url"] = [
            "slug" => $data["slug"],
            "section_id" => $data["section_id"],
            "content_id" => $data["id"]
        ];
        return $data;
    }
}
