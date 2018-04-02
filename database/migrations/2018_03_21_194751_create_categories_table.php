<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function(Blueprint $table) {
            $table->increments('id');
            $table->string('label');
            $table->string('slug');
            $table->text('locale');
            $table->integer('multi_lang_content_id');
            $table->foreign('multi_lang_content_id')->references('id')->on('multi_lang_contents')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('parent_category_id')->unsigned()->nullable();;
            $table->foreign('parent_category_id')->references('id')->on('categories')
                ->onUpdate('cascade')->onDelete('cascade');

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
        Schema::drop('categories');
    }
}
