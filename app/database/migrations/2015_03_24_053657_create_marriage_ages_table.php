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
            $table->unsignedInteger('indi_id');
            $table->enum('indi_sex', array('m', 'f', 'u'));
            $table->date('indi_birth');
            $table->unsignedInteger('fami_id');
            $table->date('fami_marriage');
            $table->integer('marriage_age');
            $table->boolean('estimated');

            $table->timestamps();

            $table->foreign('gedcom_id')->references('id')->on('gedcoms')->onDelete('cascade');
            $table->foreign('indi_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('fami_id')->references('id')->on('families')->onDelete('cascade');
            $table->index('gedcom_id');
            $table->index('indi_id');
            $table->index('fami_id');
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
