<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contents', function(Blueprint $table) {
            $table->string('title');
            $table->string('locale');
            $table->integer('section_id')->unsigned()->nullable();
            $table->string('slug');
            $table->increments('id');
            $table->integer('order')->nullable();
            $table->string('content_type');
            $table->string('status');
            $table->text('content')->nullable();
            $table->text('abstract')->nullable();
            $table->string('short_desc')->nullable();
            $table->string('tags')->nullable();
            $table->string('password')->nullable();

            $table->integer('featured_image_id')->nullable();
            $table->foreign('featured_image_id')->references('id')->on('medias');

            $table->integer('featured_video_id')->nullable();
            $table->foreign('featured_video_id')->references('id')->on('medias');

            $table->integer('multi_lang_content_id')->nullable();
            $table->foreign('multi_lang_content_id')->references('id')->on('multi_lang_contents')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
            $table->dateTimeTz('published_at')->nullable();
            $table->dateTimeTz('expired_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('contents');
    }
}
