<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return File::get(public_path().'/client/index.html');
});

Route::get('/install.php', function () {
    ob_start();
    $path = base_path("install.php");
    require($path);
    return ob_get_clean();
});

Route::get('/admin', function () {
    return File::get(public_path().'/admin/index.html');
});

Route::get('/phpinfo', function () {
    $env = getenv('APP_ENV');
    if($env === "development" || $env === "local"){
        return phpinfo();
    }
    else{
        abort(404, 'Resource not found.');
    }
});
