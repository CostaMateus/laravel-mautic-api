<?php

declare(strict_types = 1);

/*
|--------------------------------------------------------------------------
| Mautic Application Register
|--------------------------------------------------------------------------
*/

Route::get( "application/register", "MauticController@initiateApplication" );
