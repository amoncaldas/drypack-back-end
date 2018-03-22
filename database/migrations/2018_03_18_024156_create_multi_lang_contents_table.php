<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMultiLangContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('multi_lang_contents', function(Blueprint $table) {
            $table->increments('id');
            $table->string('type')->index();
            $table->integer('owner_id');
            $table->foreign('owner_id')->references('id')->on('users');
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
        Schema::drop('multi_lang_contents');
    }
}
