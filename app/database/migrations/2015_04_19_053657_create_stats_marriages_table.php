<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStatsMarriagesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stats_marriages', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->unsignedInteger('marr_event_id');
            $table->unsignedInteger('fami_id');
            $table->unsignedInteger('indi_id_husb')->nullable();
            $table->unsignedInteger('indi_id_wife')->nullable();
            $table->unsignedInteger('marr_cnt_husb')->nullable();
            $table->unsignedInteger('marr_cnt_wife')->nullable();
            $table->unsignedInteger('marr_age_husb')->nullable();
            $table->unsignedInteger('marr_age_wife')->nullable();
            $table->boolean('est_date_age_husb')->nullable();
            $table->boolean('est_date_age_wife')->nullable();
            $table->timestamps();

            $table->foreign('gedcom_id')->references('id')->on('gedcoms')->onDelete('cascade');
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
        Schema::drop('stats_marriages');
    }

}
