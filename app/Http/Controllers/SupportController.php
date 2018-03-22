<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Config;

class SupportController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Action the returns the attributes language translations
     * These translations are used also by the client
     */
    public function langs(Request $request)
    {
        $messages = array_merge(trans('auth.messages'), trans('business'), trans('mail'));
        $attributes = trans('validation.attributes');
        return [
            'attributes' => $attributes,
            'messages' => $messages
        ];
    }

    /**
     * Get the available locales
     *
     * @param Request $request
     * @return array
     */
    public function locales(Request $request){
        $locales = Config::get('i18n.locales');
        $defaultLocale = env("DEFAULT_LOCALE");
        $firstLocaleArray = [];
        if(array_key_exists($defaultLocale, $locales)){
            $locales[$defaultLocale]["default"] = true;
            $firstLocaleArray[$defaultLocale] = $locales[$defaultLocale];
            unset($locales[$defaultLocale]);
            $locales = array_merge($firstLocaleArray, $locales);
        }

        return $locales;
    }
}
