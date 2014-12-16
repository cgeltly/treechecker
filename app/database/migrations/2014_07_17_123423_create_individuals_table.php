<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIndividualsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('individuals', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->string('gedcom_key');
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('sex', array('m', 'f', 'u'));
            $table->text('gedcom');
            $table->timestamps();
            
            $table->foreign('gedcom_id')->references('id')->on('gedcoms')->onDelete('cascade');
            $table->index('gedcom_key');
            $table->index('sex');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('individuals');
    }

}
