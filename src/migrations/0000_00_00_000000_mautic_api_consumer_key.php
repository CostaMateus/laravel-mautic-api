<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MauticApiConsumerKey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() : void
    {
        Schema::create( "mautic_consumer", static function ( Blueprint $table ) : void
        {
            $table->increments( "id" );
            $table->string( "access_token" );
            $table->integer( "expires" );
            $table->string( "token_type" );
            $table->string( "refresh_token" );
            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() : void
    {
        Schema::drop( "mautic_consumer" );
    }
}
