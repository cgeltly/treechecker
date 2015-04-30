<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatsLifespansTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stats_lifespans', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->unsignedInteger('indi_id');
            $table->unsignedInteger('birth_event_id');
            $table->unsignedInteger('death_event_id');            
            $table->integer('lifespan');
            $table->boolean('est_date');

            $table->timestamps();

            $table->foreign('gedcom_id')->references('id')->on('gedcoms')->onDelete('cascade');
            $table->foreign('indi_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('birth_event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('death_event_id')->references('id')->on('events')->onDelete('cascade');            
            $table->index('gedcom_id');
            $table->index('indi_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('stats_lifespans');
    }

}
