<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableRoleActions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_actions', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('role_id');
            $table->foreign('role_id')->references('id')->on('roles');

            $table->integer('action_id');
            $table->foreign('action_id')->references('id')->on('actions');

            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_actions');
    }
}
