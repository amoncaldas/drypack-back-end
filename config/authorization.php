<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */

return [

    /** Do not remove the default roles unless you know exactly what you are doing! */
    'default_roles'=>[
        'ADMIN_ROLE_SLUG'=>'admin',
        'BASIC_ROLE_SLUG'=>'basic',
        'ANONYMOUS_ROLE_SLUG'=>'anonymous',
        'NEWS_SUBSCRIBER_ROLE_SLUG'=>'news_subscriber'
    ],

    /*
    |--------------------------------------------------------------------------
    | Allow lot listed resources
    |--------------------------------------------------------------------------
    |
    | If defined as true, allow not listed controller (resources) to be executed
    | For example: a controller (resource) named MyController is not included in the resources
    | and if allow_not_listed_controllers is true, all the methods (actions) of this controller,
    | can be requested if false, can not be requested
    |
    */
    'allow_not_listed_controllers'=>false,


    /*
    |--------------------------------------------------------------------------
    | Allow lot listed actions
    |--------------------------------------------------------------------------
    |
    | If defined as true, allow not listed actions to be executed
    | For example: a controller (resource) named MyController has an method (action)
    | named myMethod that is not included in the resource actions.
    | If allow_not_listed_actions is true, this action can be executed, if false, can not be executed
    |
    */
    'allow_not_listed_actions'=>false,

    /*
    |--------------------------------------------------------------------------
    | Available action types that can be added for resources
    |--------------------------------------------------------------------------
    |
    | These actions represent controller methods that can be associated with resources (controllers)
    | By default we add here the actions implemented by the App\Http\Controllers\CrudController, that are:
    | 'index', 'store', 'update', 'show' and 'destroy'. The wildcard action 'all' is also included.
    | Unless your application stops using the CrudController, you should not remove these actions.
    | The second section contains other actions that are present in the DryPack Framework.
    | Unless you are not using these actions (that are part of DryPack), you should not remove them
    |
    */

    'action_types' => [
        /* CrudController actions */
        'all',
        'index',
        'store',
        'update',
        'destroy',
        'show',

        /* Other DryPack actions */
        'authenticate',
        'getAuthenticatedUser',
        'listModels',
        'resources',
        'actions',
        'mapAndGet',
        'models',
        'updateProfile',
        'postEmail',
        'postReset',
        'toggleDone',
        'registerNewsLetterSubscriberUser',

        /* used by content stuff */
        'draft',
        'destroy_others',
        'index_others',
        'publish',
        'password_protect',
        'send_to_review',
        'update_others',
        'update_owner',
        'revisions',
        'revision',
        'showContent',
        'upload',
        'upload_video',
        'upload_audio',
        'upload_image',
        'upload_document',
        'save_html_media',
        'save_external_video'

        /* Custom actions */

        /* Your custom actions come here */
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources to be intercepted and verified by the Dynamic Authorization
    |--------------------------------------------------------------------------
    |
    | Each resource must have a key (the resource slug) and as value an array with:
    | - actions => 'list of actions present in this resource' (can be an slug or an array, to describe dependencies)
    | - controller_class => 'The class name of the controller'
    | - namespace => 'full namespace of the controller' (optional - if not informed, the default will be used)
    | - restricted_to_logged_users => true|false (optional, default false) defines if the resource is only acessible to logged users
    |
    */
    'resources' => [
        /*
        |--------------------------------------------------------------------------
        | Resources that are part of the DryPack functionalities
        |--------------------------------------------------------------------------

        /* Abstract wildcard resource (do not remove) */
        'all'=>['actions'=>['all','store','update','destroy', 'show']],

        // User
        'user'=>['controller_class'=>'UsersController', 'restricted_to_logged_users'=>true,
            'actions'=>['all','store','update','destroy','index', 'show', 'updateProfile', 'registerNewsLetterSubscriberUser']
        ],

        // AuditController
        'audit'=>['controller_class'=>'AuditController', 'restricted_to_logged_users'=>true,
            'actions'=>[
                'all',
                'models',
                [
                    'slug'=>'index',
                    'dependencies'=>[
                        ['resource_slug'=>'audit','action_type_slug'=>'models']
                    ]
                ]
            ]
        ],

        // DynamicQueryController
        'dataInspection'=>['controller_class'=>'DynamicQueryController', 'restricted_to_logged_users'=>true,
            'actions'=>['all','models','index']
        ],

        // MailsController
        'emails'=>['controller_class'=>'MailsController', 'restricted_to_logged_users'=>true,
            'actions'=>['store']
        ],

        // Authorization/login
        'authorization'=>['controller_class'=>'AuthorizationController', 'restricted_to_logged_users'=>true,
             'actions'=>['all','resources','actions', 'mapAndGet']
        ],

        // Reset password
        'password'=>['controller_class'=>'PasswordController',
            'actions'=>['all','postEmail',
                [
                    'slug'=>'postReset',
                    'dependencies'=>[
                        ['resource_slug'=>'password','action_type_slug'=>'postEmail']
                    ]
                ],
            ],
        ],

        // Roles
        'role'=>['controller_class'=>'RolesController', 'restricted_to_logged_users'=>true,
             'actions'=>[
                 'all',
                 [
                     'slug'=>'store',
                     'dependencies'=>[
                          ['resource_slug'=>'authorization','action_type_slug'=>'resources'],
                          ['resource_slug'=>'authorization','action_type_slug'=>'actions'],
                          ['resource_slug'=>'authorization','action_type_slug'=>'mapAndGet']
                     ]
                 ],
                 [
                     'slug'=>'update',
                     'dependencies'=>[
                          ['resource_slug'=>'authorization','action_type_slug'=>'resources'],
                          ['resource_slug'=>'authorization','action_type_slug'=>'actions'],
                          ['resource_slug'=>'authorization','action_type_slug'=>'mapAndGet']
                     ]
                 ],
                 'destroy',
                 'index',
                 'show'
             ]
        ],

        // Authentication - as we need the user identification to check the permmission
        // first we log the user in an then we check if s/he has the permission to authenticate
        'authentication'=>['controller_class'=>'AuthenticateController','restricted_to_logged_users'=>true,
            'actions'=>
            [
                'getAuthenticatedUser',
                [
                    'slug'=>'authenticate',
                    'dependencies'=>
                    [
                         ['resource_slug'=>'authentication','action_type_slug'=>'getAuthenticatedUser']
                    ]
                ]
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | This dummy resource is intended to be used to test the case when an resource
        | (represented by a controller) exist and is declared in the config/authorization.php
        | but one of its actions is not declared. If you remove this, the AuthorizationTest
        | will have one fail but your application will continue working
        |--------------------------------------------------------------------------
        */
        'dummyActionTest'=>['dummy'=>true,'namespace'=>'App\Http\Controllers\Samples','controller_class'=>'DummyActionController', 'actions'=>[]],

        // Section
        'section'=>['controller_class'=>'SectionController', 'namespace'=>'App\Http\Controllers\Content',
            'actions'=>['all','store','update','destroy','index', 'show']
        ],

         // Section
        'category'=>['controller_class'=>'CategoryController', 'namespace'=>'App\Http\Controllers\Content',
         'actions'=>['all','store','update','destroy','index', 'show']
        ],

        // Page
        'page'=>['controller_class'=>'PageController', 'namespace'=>'App\Http\Controllers\Content',
            'actions'=>[
                'all',
                [
                    'slug'=>'store',
                    'dependencies'=>[
                        ['resource_slug'=>'section','action_type_slug'=>'index']
                    ]
                ],
                [
                    'slug'=>'update',
                    'dependencies'=>[
                        ['resource_slug'=>'section','action_type_slug'=>'index']
                    ]
                ],
                'destroy',
                'index',
                'show',
                'destroy_others',
                'index_others',
                'update_others',
                'update_owner',

                /* actions that are also used as content status */
                'draft',
                'publish',
                'password_protect',
                'send_to_review',
                'revisions',
                'revision'
            ]
        ],

        // Post
        'post'=>['controller_class'=>'PostController', 'namespace'=>'App\Http\Controllers\Content',
            'actions'=>[
                'all',
                [
                    'slug'=>'store',
                    'dependencies'=>[
                        ['resource_slug'=>'section','action_type_slug'=>'index']
                    ]
                ],
                [
                    'slug'=>'update',
                    'dependencies'=>[
                        ['resource_slug'=>'section','action_type_slug'=>'index']
                    ]
                ],
                'destroy',
                'index',
                'show',
                'destroy_others',
                'index_others',
                'update_others',
                'update_owner',

                /* actions that are also used as content status */
                'draft',
                'publish',
                'password_protect',
                'send_to_review',
                'revisions',
                'revision'
            ]
        ],

        // Media
        'media'=>['controller_class'=>'MediaController', 'namespace'=>'App\Http\Controllers\Content', 'actions'=>[
            'all',
            'destroy',
            'index',
            'show',
            'store',
                ['slug' =>'save_html_media', 'dependencies'=>[['resource_slug'=>'media','action_type_slug'=>'store']]],
                ['slug' =>'save_external_video', 'dependencies'=>[['resource_slug'=>'media','action_type_slug'=>'store']]],
            'update',
            'destroy_others',
            'update_others',
            'update_owner',
            'showContent',
            'upload',
                ['slug' =>'upload_video', 'dependencies'=>[['resource_slug'=>'media','action_type_slug'=>'upload']]],
                ['slug' =>'upload_audio', 'dependencies'=>[['resource_slug'=>'media','action_type_slug'=>'upload']]],
                ['slug' =>'upload_image', 'dependencies'=>[['resource_slug'=>'media','action_type_slug'=>'upload']]],
                ['slug' =>'upload_document', 'dependencies'=>[['resource_slug'=>'media','action_type_slug'=>'upload']]]
            ]
        ],

        // Domain data
        'domain-data'=>['controller_class'=>'DomainDataController', 'namespace'=>'App\Http\Controllers\Content',
             'actions'=>['mapAndGet']
        ],

        /*
        |--------------------------------------------------------------------------
        | Here comes the resources of the samples Project and Tasks.
        | You can add your custom resources here
        |--------------------------------------------------------------------------
        */

        // Sample/Project
        'project'=>['namespace'=>'App\Http\Controllers\Samples','controller_class'=>'ProjectsController',
            'actions'=>['all','store','update','destroy','index', 'show']
        ],

        // Sample/Task
        'task'=>['namespace'=>'App\Http\Controllers\Samples','controller_class'=>'TasksController',
            'actions'=>[
                'all',
                ['slug'=>'store','dependencies'=>[['resource_slug'=>'project','action_type_slug'=>'index']]],
                ['slug'=>'update','dependencies'=>[['resource_slug'=>'project','action_type_slug'=>'index']]],
                ['slug'=>'destroy','dependencies'=>[['resource_slug'=>'project','action_type_slug'=>'index']]],
                ['slug'=>'index','dependencies'=>[['resource_slug'=>'project','action_type_slug'=>'index']]],
                ['slug'=>'show','dependencies'=>[['resource_slug'=>'project','action_type_slug'=>'index']]],
                ['slug'=>'toggleDone','dependencies'=>[['resource_slug'=>'project','action_type_slug'=>'index']]]
            ]
        ]
    ]
];
