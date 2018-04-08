<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sections', function(Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('url');
            $table->text('content')->nullable();
            $table->boolean('has_single')->default(false);
            $table->string('locale');
            $table->integer('multi_lang_content_id');
            $table->foreign('multi_lang_content_id')->references('id')->on('multi_lang_contents')->onUpdate('cascade')->onDelete('cascade');
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
        Schema::drop('sections');
    }
}
