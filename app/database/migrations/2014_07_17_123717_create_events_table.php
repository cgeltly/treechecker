<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->unsignedInteger('indi_id')->nullable();
            $table->unsignedInteger('fami_id')->nullable();
            $table->string('event');
            $table->date('date')->nullable();
            $table->boolean('est_date')->nullable();
            $table->string('datestring')->nullable();
            $table->string('place')->nullable();
            $table->double('lati')->nullable();
            $table->double('long')->nullable();
            $table->text('gedcom');
            $table->timestamps();

            $table->foreign('indi_id')->references('id')->on('individuals')->onDelete('cascade');
            $table->foreign('fami_id')->references('id')->on('families')->onDelete('cascade');
            $table->index('event');
            $table->index('gedcom_id');
            $table->index('date');
            $table->index('place');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('events');
    }

}
