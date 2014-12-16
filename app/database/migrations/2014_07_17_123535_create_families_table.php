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
            
            $table->foreign('indi_id_husb')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('indi_id_wife')->references('id')->on('individuals')->onDelete('cascade');
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
        Schema::drop('families');
    }

}
