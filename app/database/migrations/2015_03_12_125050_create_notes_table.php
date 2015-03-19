<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notes', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->string('gedcom_key')->nullable();
            $table->text('note');
            $table->unsignedInteger('fami_id')->nullable();
            $table->unsignedInteger('indi_id')->nullable();
            $table->unsignedInteger('even_id')->nullable();
            $table->text('gedcom');
            $table->timestamps();

            $table->foreign('gedcom_id')->references('id')->on('gedcoms')->onDelete('cascade');
            $table->foreign('fami_id')->references('id')->on('families')->onDelete('cascade');
            $table->foreign('indi_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('even_id')->references('id')->on('events')->onDelete('cascade');
            $table->index('gedcom_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('notes');
    }

}
