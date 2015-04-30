<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSourcesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sources', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->string('gedcom_key');
            $table->string('title');
            $table->unsignedInteger('fami_id')->nullable();
            $table->unsignedInteger('indi_id')->nullable();
            $table->unsignedInteger('even_id')->nullable();
            $table->unsignedInteger('note_id')->nullable();
            $table->text('gedcom');
            $table->timestamps();

            $table->foreign('gedcom_id')->references('id')->on('gedcoms')->onDelete('cascade');
            $table->foreign('fami_id')->references('id')->on('families')->onDelete('cascade');
            $table->foreign('indi_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('even_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('note_id')->references('id')->on('events')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sources');
    }

}
