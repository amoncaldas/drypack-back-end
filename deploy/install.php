<?php

/**
 * Install a package, run migration (options) and remove unnecessary files
 */

$pkgName = "appPack.zip";
$installerFile = 'install.php';
$url = getBaseUrl();
$dir = dirname(__FILE__);

$migrate = false;
$seed = false;

if(isset($_GET['migrate'])) {
    $migrate = htmlspecialchars($_GET['migrate']);
}
if(isset($_GET['seed'])) {
    $seed = htmlspecialchars($_GET['seed']);
}

try {
    $file=scandir($dir);
    $cont = count($file);
    $content = '';

    for ($i=0; $i < $cont; $i++) {
        if ($file[$i] != $pkgName && $file[$i] != $installerFile
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
        $extractResult = $zip->extractTo($dir);
        //var_dump($extractResult);

        $zip->close();
        if($migrate === true || $migrate === "true"){
            echo shell_exec("cd $dir && php artisan migrate");
        }

        if($seed === true || $seed === "true"){
            echo shell_exec("cd $dir && php artisan seed");
        }

        echo shell_exec("cd $dir && rm -rf $installerFile $pkgName");
        http_response_code(200);
        header(trim("HTTP/1.0 200"));

    } else {
        header(trim("HTTP/1.0 400 Could not open $dir/$installerFile"));
    }

} catch (Exception $e) {
    $error =  $e->getMessage();
    header(trim("HTTP/1.0 400 $error"));
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
