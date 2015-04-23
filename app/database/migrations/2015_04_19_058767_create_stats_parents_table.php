<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatsParentsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stats_parents', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->unsignedInteger('fami_id');
            $table->unsignedInteger('par_id');
            $table->unsignedInteger('chil_id');
            $table->unsignedInteger('par_birth_event_id');
            $table->unsignedInteger('chil_birth_event_id');            
            $table->integer('par_age')->nullable();
            $table->boolean('est_date');
            $table->enum('par_sex', array('m', 'f'));

            $table->timestamps();

            $table->foreign('chil_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('par_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('fami_id')->references('id')->on('families')->onDelete('cascade');
            $table->foreign('par_birth_event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('chil_birth_event_id')->references('id')->on('events')->onDelete('cascade');            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('stats_parents');
    }

}
