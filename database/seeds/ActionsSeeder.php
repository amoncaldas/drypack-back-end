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

/**
 * Remove all the existing actions and roles and re(store) the actions based in the config file
 * Then calls the UsersAndRolesSeeder to refresh the roles actions
 */
class ActionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AuthorizationSetup::removeActionsRoles();

        // Here we retrive the declared resources in config/authorization.php
        $resourcesWithActions = Authorization::getResources();

        // These resources, its possible actions and each action collection of actions dependence are inserted.
        // As there are some specific rules and procedures related to the DryPack Dynamic Authorization these seeds are done by App\Authorization\Authorization
        AuthorizationSetup::storeResourcesActions($resourcesWithActions);

        // After running the actions seeder, is useful run the UsersAndRolesSeeder
        // because all the existing actions were removed and recreated. So, it is important (re)set the
        // the roles actions. In this seeder, the users are not recreated, if they already exist
        $this->call(UsersAndRolesSeeder::class);

    }
}
