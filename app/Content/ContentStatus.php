<?php

namespace App\Content;
use \Lang;

class ContentStatus
{
    // these status represent also actions in config/authorization.php
    public static $draft = ["slug"=>"draft", "action_slug"=>"draft"];
    public static $published = ["slug"=>"published", "action_slug"=>"publish"];
    public static $passwordProtected = ["slug"=>"password_protected", "action_slug"=>"password_protect"];
    public static $pendingReview = ["slug"=>"pending_review", "action_slug"=>"send_to_review"];

    public static function all() {
        return [
            static::$draft,
            static::$published,
            static::$passwordProtected,
            static::$pendingReview
        ];
    }

    public static function allKeys() {
        return [
            static::$draft["slug"],
            static::$published["slug"],
            static::$passwordProtected["slug"],
            static::$pendingReview["slug"]
        ];
    }

    public static function allTrans() {
        return [
            static::translation(static::$draft["slug"]),
            static::translation(static::$published["slug"]),
            static::translation(static::$passwordProtected["slug"]),
            static::translation(static::$pendingReview["slug"])
        ];
    }

    public static function allWithTrans() {
        $all = [];
        foreach (static::all() as $value) {
            $value["desc"] = static::translation($value["slug"]);
            $all[] = $value;
        }
        return $all;
    }

    public static function translation($status) {
        if(is_array($status)) {
            return Lang::get("status.".$status["slug"]);
        }
        return Lang::get("status.".$status);
    }

    public static function getStatusAction($status_to_search) {
        $all= static::all();
        foreach ($all as $status) {
            if(is_array($status_to_search)) {
                if ($status["slug"] === $status_to_search["slug"]) {
                    return $status["action_slug"];
                }
            } elseif ($status["slug"] === $status_to_search) {
                return $status["action_slug"];
            }

        }
    }

}
