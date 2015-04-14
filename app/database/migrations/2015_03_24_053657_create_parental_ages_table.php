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
            $table->unsignedInteger('par_id');
            $table->unsignedInteger('chil_id');
            $table->integer('par_age')->nullable();
            $table->boolean('est_date');
            $table->enum('par_sex', array('m', 'f'));

            $table->timestamps();

            $table->foreign('chil_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('par_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('fami_id')->references('id')->on('families')->onDelete('cascade');
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
