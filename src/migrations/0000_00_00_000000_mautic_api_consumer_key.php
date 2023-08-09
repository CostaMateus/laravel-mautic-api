<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class MauticApiConsumerKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mautic_consumer', function (Blueprint $table) {
            $table->increments('id');
            $table->string('access_token');
            $table->integer('expires');
            $table->string('token_type');
            $table->string('refresh_token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('mautic_consumer');
    }
}
