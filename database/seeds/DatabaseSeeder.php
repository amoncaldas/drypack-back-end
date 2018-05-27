<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Role;
use App\Project;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // Dynamic authorization seeders
        // The ActionsSeeder internally calls the UsersAndRolesSeeder
        $this->call(ActionsSeeder::class);

        $this->call(ContentSeeder::class);

        // Adding here references to other seeders

        Model::reguard();
    }
}
