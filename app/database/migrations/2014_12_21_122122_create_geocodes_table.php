<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeocodesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('geocodes', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->string('place')->nullable();
            $table->double('lati');
            $table->double('long');
            $table->text('gedcom');
            $table->timestamps();
            
            $table->foreign('gedcom_id')->references('id')->on('gedcoms')->onDelete('cascade');
            
            $table->index('place'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('geocodes');
    }

}
