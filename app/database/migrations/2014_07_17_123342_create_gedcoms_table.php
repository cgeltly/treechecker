<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGedcomsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gedcoms', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('file_name');
            $table->string('path');
            $table->string('tree_name');
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('parsed')->default(false);
            $table->boolean('error_checked')->default(false);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index('parsed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('gedcoms');
    }

}
