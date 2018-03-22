<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableActionsDependencies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actions_dependencies', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('dependent_action_id');
            $table->foreign('dependent_action_id')->references('id')->on('actions');

            $table->integer('depends_on_action_id');
            $table->foreign('depends_on_action_id')->references('id')->on('actions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('actions_dependencies');
    }
}
