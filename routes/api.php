<?php

use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// pre-flight to CORS
Route::options('{all}', function() {
    // threat the OPTIONS pre-flight request
    return \Response::json('{"method":"OPTIONS"}', 200, \DryPack::getCORSHeaders());
})->where('all', '.*');

Route::group(['prefix' => 'v1', 'middleware' => ['cors', 'i18n']], function () {

    /*
    |----------------------------------------------------------------------------------------
    | Non authenticated area and not passing through the dynamic permissions verification
    |----------------------------------------------------------------------------------------
    */
    Route::get('authenticate/check', function () {
    	return response()->json(['status' => 'valid']);
    })->middleware('jwt.auth'); //just to check the token

    Route::group(['prefix' => 'support'], function () {
        Route::get('langs', 'SupportController@langs');
        Route::get('locales', 'SupportController@locales');
    });

    // First we log the user an then, in the AuthenticateController the 'dyn.permission' is executed
    Route::post('authenticate', 'AuthenticateController@authenticate');

    /*
    |----------------------------------------------------------------------------------------
    | Actions that need authentication and dynamic permission
    |----------------------------------------------------------------------------------------
    */
    Route::group(['middleware' => ['dyn.permission']], function () {

        Route::post('password/email', 'PasswordController@postEmail');
        Route::post('password/reset', 'PasswordController@postReset');
        Route::resource('sections', 'Content\SectionController');

        Route::get('/{contentType}/{contentId}/revisions', function(Request $request, $contentType, $contentId) {
            $klass = "\App\Http\Controllers\Content\\".ucfirst(trim($contentType, "s"))."Controller";
            $controller = new $klass();
            return $controller->revisions($request, $contentId);
        })->where(['content', '(pages|posts)']);

        Route::get('/{contentType}/{contentId}/revisions/{revisionId}', function(Request $request, $contentType, $contentId, $revisionId) {
            $klass = "\App\Http\Controllers\Content\\".ucfirst(trim($contentType, "s"))."Controller";
            $controller = new $klass();
            return $controller->revision($request, $contentId, $revisionId);
        })->where(['content', '(pages|posts)']);

        Route::resource('pages', 'Content\PageController');
        Route::resource('posts', 'Content\PostController');


        Route::resource('categories', 'Content\CategoryController');

        // This route maps the request to /domain-data/{domainName} using generic service
        Route::get('/domain-data/{domainName}', 'Content\DomainDataController@mapAndGet');

        // Samples
        Route::resource('projects', 'Samples\ProjectsController');
        Route::put('tasks/toggleDone', 'Samples\TasksController@toggleDone');
        Route::resource('tasks', 'Samples\TasksController');

        Route::resource('mails', 'MailsController', ['only' => ['store']]);
        Route::put('profile', 'UsersController@updateProfile');
        Route::post('nesw/subscribe', 'UsersController@registerNewsLetterSubscriberUser');

        // This route maps the request to /authorization/actions using generic service
        Route::get('/authorization/{domainName}', 'AuthorizationController@mapAndGet');

        Route::get('authenticate/user', 'AuthenticateController@getAuthenticatedUser');

        Route::resource('roles', 'RolesController');

        Route::get('audit', 'AuditController@index');
        Route::get('audit/models', 'AuditController@models');
        Route::resource('users', 'UsersController', ['except' => ['updateProfile', 'registerNewsLetterSubscriberUser']]);
        Route::group(['prefix' => 'dynamic-query'], function () {
            Route::get('/', 'DynamicQueryController@index');
            Route::get('models', 'DynamicQueryController@models');
        });

        /**
         * Media controller and upload
         */
        Route::resource('medias', 'Content\MediaController', ['except' => ['upload', 'showContent']]);
        Route::post('media/upload', 'Content\MediaController@upload');
        Route::get('medias/{id}/content', 'Content\MediaController@showContent');

        // Route::get('media/content/{id}', function(Request $request, $id) {
        //     $klass = "\App\Http\Controllers\Content\"MediaController";
        //     $controller = new $klass();
        //     return $controller->showContent($request, $id);
        // });

        /**
         * This dummy route is intended to be used to test the case
         * when an resource (represented by a controller) exist but is not declared on the
         * config/authorization.php. If you remove this, the AuthorizationTest will have one fail
         * but your application will continue working
         */
        Route::get('dummy-resource/method', 'Samples\DummyResourceController@dummyMethod');

        /**
         * This dummy route is intended to be used to test the case
         * when an resource (represented by a controller) exist and is declared in the config/authorization.php
         * but one os tis actions is not declared. If you remove this, the AuthorizationTest will have one fail
         * but your application will continue working
         */
        Route::get('dummy-action/method', 'Samples\DummyActionController@dummyMethod');
    });
});
