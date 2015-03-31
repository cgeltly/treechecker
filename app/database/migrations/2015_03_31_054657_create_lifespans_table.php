<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLifespansTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lifespans', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->unsignedInteger('indi_id')->nullable();            
            $table->unsignedInteger('lifespan')->nullable();
            $table->enum('est_date', array('0', '1'));
            
            $table->timestamps();
            
            $table->foreign('indi_id')->references('id')->on('individuals')->onDelete('cascade');
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
        Schema::drop('lifespans');
    }

}
