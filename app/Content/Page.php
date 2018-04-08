<?php

namespace App\Content;

use App\Content\Content;

class Page extends Content
{
    protected function getContentType() {
        return "page";
    }

    public function newQuery()
    {
        $query = parent::newQuery();
        $type = $this->getContentType();
        $query = $query->where('content_type','=', $type);
        return $query;
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
     * Override the base toArray method to include custom attributes
     *
     * @return array with model's data
     */
    public function toArray() {
        $data = parent::toArray();
        return $data;
    }
}
