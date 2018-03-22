<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->text('description');
            $table->boolean('done');
            $table->tinyInteger('priority');
            $table->dateTimeTz('scheduled_to')->nullable();

            $table->integer('project_id')->unsigned();
            $table->foreign('project_id')->references('id')->on('projects')
                ->onDelete('cascade');

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
        Schema::drop('tasks');
    }
}
