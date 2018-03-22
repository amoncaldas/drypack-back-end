<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableActions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //delete old tables
        Schema::dropIfExists('roles_resources_actions');
        Schema::dropIfExists('action_dependencies');
        Schema::dropIfExists('actions');
        Schema::dropIfExists('resources');

        Schema::create('actions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('action_type_slug');
            $table->string('resource_slug');
            $table->unique(array('resource_slug', 'action_type_slug'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('actions');
    }
}
