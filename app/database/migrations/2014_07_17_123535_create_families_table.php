<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFamiliesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('families', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->string('gedcom_key');
            $table->unsignedInteger('indi_id_husb')->nullable();
            $table->unsignedInteger('indi_id_wife')->nullable();
            $table->text('gedcom');
            $table->timestamps();
            
            $table->foreign('gedcom_id')->references('id')->on('gedcoms')->onDelete('cascade');
            $table->index('gedcom_id');    
            $table->index('indi_id_husb'); 
            $table->index('indi_id_wife');             
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('families');
    }

}
