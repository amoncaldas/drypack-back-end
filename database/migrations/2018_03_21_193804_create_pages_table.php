<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pages', function(Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('slug');
            $table->string('locale');
            $table->string('status');
            $table->text('content')->nullable();
            $table->string('abstract')->nullable();
            $table->string('short_desc')->nullable();
            $table->string('password')->nullable();

            $table->integer('featured_image_id')->nullable();
            $table->foreign('featured_image_id')->references('id')->on('medias');

            $table->integer('multi_lang_content_id')->nullable();
            $table->foreign('multi_lang_content_id')->references('id')->on('multi_lang_contents')->onUpdate('cascade')->onDelete('cascade');

            $table->integer('section_id')->nullable();
            $table->foreign('section_id')->references('id')->on('sections')->onUpdate('cascade')->onDelete('cascade');

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
        Schema::drop('pages');
    }
}
