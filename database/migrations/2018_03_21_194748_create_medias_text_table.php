<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMediasTextTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medias_text', function(Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('desc');
            $table->string('locale');
            $table->integer('media_id')->nullable();
            $table->foreign('media_id')->references('id')->on('medias')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('medias_text');
    }
}
