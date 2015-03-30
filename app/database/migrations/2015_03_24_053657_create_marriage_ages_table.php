<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarriageAgesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marriage_ages', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->unsignedInteger('fami_id')->nullable();
            $table->unsignedInteger('husb_id')->nullable();            
            $table->unsignedInteger('wife_id')->nullable();
            $table->unsignedInteger('marr_event_id')->nullable();            
            $table->unsignedInteger('marr_husb_age')->nullable();
            $table->unsignedInteger('marr_wife_age')->nullable();
            $table->enum('marr_husb_est_date', array('0', '1'));
            $table->enum('marr_wife_est_date', array('0', '1'));            
            
            $table->timestamps();
            
            $table->foreign('gedcom_id')->references('id')->on('gedcoms')->onDelete('cascade');
            $table->foreign('husb_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('wife_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('fami_id')->references('id')->on('families')->onDelete('cascade');
            $table->foreign('marr_event_id')->references('id')->on('events')->onDelete('cascade');            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('marriage_ages');
    }

}
