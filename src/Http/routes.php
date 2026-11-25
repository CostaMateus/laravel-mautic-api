<?php

use Illuminate\Support\Facades\Route;
use CostaMateus\Mautic\Http\Controllers\MauticController;

/*
|--------------------------------------------------------------------------
| Mautic Application Register
|--------------------------------------------------------------------------
*/

Route::get( "application/register", [MauticController::class, "initiateApplication"] );
