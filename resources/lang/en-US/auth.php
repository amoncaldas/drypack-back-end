<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Roles translations
    |--------------------------------------------------------------------------
    */
    'roles'=> [
        'admin'=>'Admin',
        'basic'=>'Basic',
        'anonymous'=>'Anonymous'
    ],
    /*
    |--------------------------------------------------------------------------
    | Authorization Language strings/labels
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the dynamic authorization component t
    | o the output list of resources and actions available.
    | You can customize labels your views to better match your application.
    |
    */
    'resources'=> [
        'all'=>'All',
        'users'=>'User',
        'audit'=>'Audit',
        'dataInspection'=>'Dynamic Query',
        'emails'=>'Emails',
        'authorization'=>'Authorization',
        'password'=>'Password',
        'role'=>'Roles',
        'authentication'=>'Authentication',
        'project'=>'Project',
        'task'=>'Task'
    ],
    'actions'=> [
        'all'=>'All',
        'index'=>'List',
        'store'=>'Store',
        'update'=>'Update',
        'destroy'=>'Remove',
        'show'=>'Show',
        'authenticate'=>'Authenticate',
        'getAuthenticatedUser'=>'Retrive authenticated user',
        'listModels'=>'List models',
        'resources'=>'List resources',
        'actions'=>'List actions',
        'mapAndGet'=>'List data',
        'models'=>'List resources',
        'updateProfile'=>'Update profile',
        'postEmail'=>'Request password reset',
        'postReset'=>'Save reseted password'
    ],
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */
    'messages'=> [
        'no_authorization_for_this_resource'=>'Without permission to access this resource/page',
        'no_authorization_for_this_type_of_action_in_resource'    => 'Without permission for the action over :resourceName',
        'no_authorization_for_action_in_resource'    => 'Without the permission :actionName over :resourceName',
        'mandatory_admin_profile_auto_added'=>"This user must have the admin role then it was automatically added",
        'mandatory_permissions_added'=>"Minimum necessary permissions to the Admin role were automatically added"
    ],
];
