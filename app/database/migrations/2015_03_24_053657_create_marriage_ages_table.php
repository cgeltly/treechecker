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
            $table->unsignedInteger('indi_id_husb')->nullable();            
            $table->unsignedInteger('indi_id_wife')->nullable();
            $table->unsignedInteger('marr_age_husb')->nullable();
            $table->unsignedInteger('marr_age_wife')->nullable();
            $table->enum('est_date_age_husb', array('0', '1'));
            $table->enum('est_date_age_wife', array('0', '1'));            
            
            $table->timestamps();
            
            $table->foreign('indi_id_husb')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('indi_id_wife')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('fami_id')->references('id')->on('families')->onDelete('cascade');
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
        Schema::drop('marriage_ages');
    }

}