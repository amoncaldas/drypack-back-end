<?php

namespace App\Http\Middleware;

use \Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

use Closure;

class I18n
{
    /**
     * Middleware that treats the CORS, adding them to the header
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $this->getLocale($request);

        \App::setLocale($locale);

        $response = $next($request);
        $response = $response->header('Locale', $locale);
        return $response;
    }

    /**
     * Get locale
     *
     * @param Request $request
     * @return string
     */
    protected function getLocale(Request $request){
        $locale = $request->headers->get('Locale');
        if(!isset($locale)) {
            $locale = $this->getRequestLocale();
            if(!isset($locale)) {
                $locale = env("DEFAULT_LOCALE");
            }
        } else {
            if(!array_key_exists($locale, Config::get('i18n.locales'))){
                $locale = env("DEFAULT_LOCALE");
            }
        }

        return $locale;
    }

    /**
     * Get request locale
     *
     * @return string
     */
    protected function getRequestLocale(){
        if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            $locale_strings = explode(",",$_SERVER['HTTP_ACCEPT_LANGUAGE']);
            if(strpos($locale_strings[0], "-") > -1)
            {
                $locale = explode("-",$locale_strings[0])[0];
                if (array_key_exists($locale, Config::get('i18n.locales'))){
                    return $locale;
                }
            }
        }
    }

}
