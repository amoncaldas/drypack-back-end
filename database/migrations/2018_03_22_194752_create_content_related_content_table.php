<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContentRelatedContentTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_related_content', function(Blueprint $table)
        {

            $table->integer('content_id')->unsigned();
            $table->integer('related_content_id')->unsigned();
            $table->string('related_content_type')->unsigned();

            $table->foreign('content_id')->references('id')->on('contents')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('related_content_id')->references('id')->on('contents')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['content_id', 'related_content_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('content_related_content');
    }

}
