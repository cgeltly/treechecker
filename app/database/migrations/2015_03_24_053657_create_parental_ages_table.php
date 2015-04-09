<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateParentalAgesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parental_ages', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->unsignedInteger('fami_id');
            $table->unsignedInteger('pare_id');
            $table->unsignedInteger('chil_id');
            $table->enum('pare_sex', array('m', 'f', 'u'));
            $table->date('pare_birth');
            $table->date('chil_birth');
            $table->integer('parental_age');
            $table->boolean('estimated');

            $table->timestamps();

            $table->foreign('gedcom_id')->references('id')->on('gedcoms')->onDelete('cascade');
            $table->foreign('fami_id')->references('id')->on('families')->onDelete('cascade');
            $table->foreign('pare_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('chil_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->index('gedcom_id');
            $table->index('fami_id');
            $table->index('pare_id');
            $table->index('chil_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('parental_ages');
    }

}
