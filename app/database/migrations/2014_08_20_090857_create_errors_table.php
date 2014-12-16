<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateErrorsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('errors', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->unsignedInteger('indi_id')->nullable();
            $table->unsignedInteger('fami_id')->nullable();
            $table->enum('stage', array('parsing', 'error_check'))->default('error_check');
            $table->enum('classification', array('missing', 'superfluous', 'incorrect'));
            $table->enum('severity', array('fatal', 'error', 'warning'));
            $table->string('message');
            $table->timestamps();
            
            $table->foreign('gedcom_id')->references('id')->on('gedcoms')->onDelete('cascade');
            $table->foreign('indi_id')->references('id')->on('individuals')->onDelete('cascade');
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
        Schema::drop('errors');
    }

}
