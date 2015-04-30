<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSystemsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('systems', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');
            $table->unsignedInteger('gedcom_id');
            $table->string('system_id');
            $table->string('version_number')->nullable();
            $table->string('product_name')->nullable();
            $table->string('corporation')->nullable();
            $table->text('gedcom');
            $table->timestamps();

            $table->foreign('gedcom_id')->references('id')->on('gedcoms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('systems');
    }

}
