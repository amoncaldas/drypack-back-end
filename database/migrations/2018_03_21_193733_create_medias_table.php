<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMediasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medias', function(Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->string('status');
            $table->string('mimetype');
            $table->string('length')->nullable();
            $table->string('file_name')->nullable();
            $table->text('content')->nullable();
            $table->text('thumb')->nullable();
            $table->string('url')->nullable();
            $table->string('author_name')->nullable();

            $table->string('unique_name')->nullable();
            $table->string('ext')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('width_unit')->nullable();
            $table->string('height_unit')->nullable();
            $table->string('preview_image')->nullable();
            $table->string('dimension_type')->nullable(); // responsive, sized
            $table->string('storage_policy'); // 'filesystem' or 'indb'

            $table->integer('owner_id');
            $table->foreign('owner_id')->references('id')->on('users');

            $table->integer('author_id')->nullable();
            $table->foreign('author_id')->references('id')->on('users');
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
        Schema::drop('medias');
    }
}
