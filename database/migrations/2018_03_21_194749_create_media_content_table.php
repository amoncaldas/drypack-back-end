<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMediaContentTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_content', function(Blueprint $table)
        {
            $table->integer('media_id')->unsigned();
            $table->integer('content_id')->unsigned();
            $table->string('content_type')->unsigned();

            $table->foreign('media_id')->references('id')->on('medias')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('content_id')->references('id')->on('contents')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['media_id', 'content_id', 'content_type']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('media_content');
    }

}
