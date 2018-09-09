<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */


use Illuminate\Database\Seeder;
use App\Authorization\Authorization;
use App\Authorization\AuthorizationSetup;
use App\Authorization\Action;
use App\Role;
use App\User;
use Carbon\Carbon;

class UsersAndRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /* ------------------------------------------------------------------------------------------------/
        | In this seeder the default roles and users are created (if they do not exist)
        | and then the the default actions to the default roles are set
        | You can customize this. Remember that when the ActionsSeeder runs
        | internally it calls this seeder!
        |
        | You can create custom roles and set the actions allowed to it using one of the following methods:
        |   - AuthorizationSetup::setResourcesActionsForRole // pass an array of resource slugs and a Role
        |   - AuthorizationSetup::setActionsForRole // pass a Collection of actions and a Role
        ---------------------------------------------------------------------------------------------------*/

        // Get the admin role based in the default definitionin config/authorization.php
        $adminRole = $this->getRole(Role::defaultAdminRoleSlug());

        // We can use wildcards, like "*" or "all" to set all the permissions
        AuthorizationSetup::setResourcesActionsForRole("*", $adminRole);

        // Create the ADMIN user (if it not exists) and attach the role admin to it
        $adminUser = $this->getUser(env('DEFAULT_ADMIN_USER_EMAIL'), Role::defaultAdminRoleSlug());
        if(!$adminUser->roles->contains($adminRole->id)){
            $adminUser->roles()->attach($adminRole->id);
        }

        // Get the basic role based in the default definitionin config/authorization.php
        $basicRole = $this->getRole(Role::defaultBasicRoleSlug());

        // We can use resource:action to set an specific action in a resource
        // and set CRUD permissions to the resources "authentication", "project", "task" and "user:updateProfile" to it
        $actions_filter = [
            "authentication", "password", "project",
            "task", "user:updateProfile", "section", "page",
            "category", "domain-data", "post", "media"];
        AuthorizationSetup::setResourcesActionsForRole($actions_filter,$basicRole);

        // Create the NORMAL user (if it not exists) and attach the role NORMAL to it
        $basicUser = $this->getUser(env('DEFAULT_BASIC_USER_EMAIL'), Role::defaultBasicRoleSlug());
        if(!$basicUser->roles->contains($basicRole->id)){
            $basicUser->roles()->attach($basicRole->id);
        }

        // Create the ANONYMOUS role (if it does not exist)
        $actions_filter = [
            "project:index",
            "project:show",
            "task:index",
            "task:show",
            "page:index",
            "page:show",
            "post:index",
            "post:show",
            "password",
            "media:showContent",
            "authentication:authenticate",
            "user:registerNewsLetterSubscriberUser"
        ];
        AuthorizationSetup::setResourcesActionsForRole($actions_filter, $this->getRole(Role::anonymousRoleSlug()));

        // Create 5 additional random users with no permissions  - disabled, you can enabled if needed
        // factory(User::class, 5)->create();

        // Create the subscriber Role, if it does not exist
        $this->getRole(Role::newsSubscriberRoleSlug());
    }

    /**
     * Get a Role (it it does dot exist, create it)
     * @param string $role_slug
     * @return App\Role
     */
    function getRole($role_slug){
        $role = Role::where('slug', $role_slug)->first();
        if(!isset($role)){
            $role = factory(Role::class)->states($role_slug)->create();
        }
        return $role;
    }

    /**
     * Get a User (it it does dot exist, create it)
     * @param string $user_mail
     * @param string $user_state [basic|admin]
     * @return App\Role
     */
    function getUser($user_mail, $user_state){
        // Create the NORMAL user (if it not exists) and attach the role NORMAL to it
        $user = User::where('email', $user_mail)->first();
        if(!isset($user)){
            $user = factory(User::class)->states($user_state)->create();
        }
        return $user;
    }
}
