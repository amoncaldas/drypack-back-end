<?php

namespace App\Util;

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
     * Get the list of app models from the app dir
     *
     * @param array $ignoredModels list of files representing models to be ignored
     * @return array list of string wit the model's name
     */
    public static function modelNames($ignored_model_files = array())
    {
        $models = array();
        $path = app_path();
        $files = scandir($path);

        foreach ($files as $file) {
            // skip all dirs and ignored_model_files
            if ($file === '.' || $file === '..' || is_dir($path . '/' . $file) || in_array($file, $ignored_model_files)) {
                continue;
            }

            $models[] = preg_replace('/\.php$/', '', $file);
        }

        return $models;
    }

    public static function getSlug($string){
         // Remove special accented characters - ie. sí.
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
}
