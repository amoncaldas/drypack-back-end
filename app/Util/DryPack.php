<?php

namespace App\Util;

use Illuminate\Support\Facades\Config;

class DryPack
{

     /**
     * Return the headers related to the CORS
     *
     * @return array with the headers related to the CORS
     */
    public static function getCORSHeaders()
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Headers' => 'Origin, Content-Type, Accept, Authorization, X-Requested-With',
            'Access-Control-Allow-CredentialsHeaders' => 'true'
        ];
    }

    /**
     * Transforms a date string in a Carbon object
     *
     * @param $date date as string
     * @return \Carbon\Carbon date object
     */
    public static function parseDate($date)
    {
        return \Carbon::parse($date)->timezone(config('app.timezone'));
    }

    /**
     * Get the list of app models according defined in condig/dynamic-query.php
     *
     * @return array list of string wit the model's namespace and name
     */
    public static function loadableModels()
    {
        $models = array();

        $locations = Config::get('dynamic-query.model-locations');

        foreach ($locations as $location) {
            $files = scandir($location['path']);

            foreach ($files as $file) {
                if(!in_array($file,$location['exclusions']) && $file !== '.' && $file !== '..' && !is_dir($location['path'] . '/' . $file)) {
                    $model = preg_replace('/\.php$/', '', $file);
                    if(isset($location['namespace'])) {
                        $model = $location['namespace']."\\".$model;
                    } else {
                        'App\\'.$model;
                    }
                    $models[] = $model;
                }
            }
        }

        return $models;
    }

    /**
     * Replace special characters for the non special corresponding ones
     *
     * @param string $string
     * @return string
     */
    public static function getSlug($string){
        if(!isset($string)){
            return $string;
        }
        $clean_name = strtr($string, array('Š' => 'S','Ž' => 'Z','š' => 's','ž' => 'z','Ÿ' => 'Y','À' => 'A','Á' => 'A','Â' => 'A','Ã' => 'A','Ä' => 'A','Å' => 'A','Ç' => 'C','È' => 'E','É' => 'E','Ê' => 'E','Ë' => 'E','Ì' => 'I','Í' => 'I','Î' => 'I','Ï' => 'I','Ñ' => 'N','Ò' => 'O','Ó' => 'O','Ô' => 'O','Õ' => 'O','Ö' => 'O','Ø' => 'O','Ù' => 'U','Ú' => 'U','Û' => 'U','Ü' => 'U','Ý' => 'Y','à' => 'a','á' => 'a','â' => 'a','ã' => 'a','ä' => 'a','å' => 'a','ç' => 'c','è' => 'e','é' => 'e','ê' => 'e','ë' => 'e','ì' => 'i','í' => 'i','î' => 'i','ï' => 'i','ñ' => 'n','ò' => 'o','ó' => 'o','ô' => 'o','õ' => 'o','ö' => 'o','ø' => 'o','ù' => 'u','ú' => 'u','û' => 'u','ü' => 'u','ý' => 'y','ÿ' => 'y'));

        $clean_name = strtr($clean_name, array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss', 'Œ' => 'OE', 'œ' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u'));

        $words = explode(" ",$clean_name);
        $slug = '';
        foreach ($words as $word) {
            $str = strtolower($word);
            if($slug !== ''){
                $slug .= ucfirst($str);
            }
            else{
               $slug .= $str;
            }
        }
        return $slug;
    }

    /**
     * Check if a string ends with a substring
     *
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    public static function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    /**
     * Check if a string starts with a substring
     *
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    public static  function startsWith($haystack, $needle) {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    /**
     * Check if a string contains a substring
     *
     * @param string $haystack
     * @param string $needle
     * @return boolean
     */
    public static  function contains($haystack, $needle) {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle) !== false;
    }
}
