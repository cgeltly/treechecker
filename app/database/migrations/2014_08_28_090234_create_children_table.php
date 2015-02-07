<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChildrenTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('children', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->unsignedInteger('fami_id');
            $table->unsignedInteger('indi_id');
            $table->timestamps();
            
            $table->foreign('fami_id')->references('id')->on('families')->onDelete('cascade');
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
        Schema::drop('children');
    }

}
