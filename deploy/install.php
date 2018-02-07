<?php

/**
 * pkName is gonna be the same of the PKG_NAME with the .zip extension
 * Url must be the full url with the http://
 */

$pkgName = "appPack.zip";
$installFile = 'install.php';
$dockerFile = 'Dockerfile';
$url = getBaseUrl();
$dir = dirname(__FILE__);
$migrate = htmlspecialchars($_GET['migrate']);

try {
    $file=scandir($dir);
    $cont = count($file);
    $content = '';

    for ($i=0; $i < $cont; $i++) {
        if ($file[$i] != $pkgName && $file[$i] != $installFile && $file[$i] != $dockerFile
                && $file[$i] != '.' && $file[$i] != '..') {
            $content .= $file[$i] . " ";
        }
    }

    if ($cont > 4) {
        echo shell_exec('rm -rf ' . $content);
    }

    $zip = new ZipArchive();
    $open = $zip->open($pkgName);
    if( $open === true){

        @set_time_limit(300); # 5 MINUTES
        $zip->extractTo($dir);

        $zip->close();

        if($migrate === "true"){
            echo shell_exec("cd $dir && artisan php migrate");
        }

        echo shell_exec("cd $dir && rm -rf $installFile $dockerFile $pkgName");

        http_response_code(200);
        //header("location:$url");

    } else {
        http_response_code(400);
        //echo $open;
    }

} catch (Exception $e) {
    echo $e->getMessage();
}

/**
 * Suppose, you are browsing in your localhost
 * http://localhost/myproject/index.php?id=8
 */
function getBaseUrl()
{
    // output: /myproject/index.php
    $currentPath = $_SERVER['PHP_SELF'];

    // output: Array ( [dirname] => /myproject [basename] => index.php [extension] => php [filename] => index )
    $pathInfo = pathinfo($currentPath);

    // output: localhost
    $hostName = $_SERVER['HTTP_HOST'];

    // output: http://
    $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,5))=='https://'?'https://':'http://';

    // return: http://localhost/myproject/
    return $protocol.$hostName.$pathInfo['dirname']."/";
}

?>
