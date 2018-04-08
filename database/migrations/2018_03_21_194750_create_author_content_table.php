<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuthorContentTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('author_content', function(Blueprint $table)
        {
            $table->integer('author_id')->unsigned();
            $table->integer('content_id')->unsigned();
            $table->string('content_type')->unsigned();

            $table->foreign('author_id')->references('id')->on('users')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('content_id')->references('id')->on('contents')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->primary(['author_id', 'content_id', 'content_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('author_content');
    }

}
