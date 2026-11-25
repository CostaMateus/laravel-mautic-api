<?php

use Illuminate\Support\Facades\Route;
use Triibo\Mautic\Http\Controllers\MauticController;

/*
|--------------------------------------------------------------------------
| Mautic Application Register
|--------------------------------------------------------------------------
*/

Route::get( "application/register", [MauticController::class, "initiateApplication"] );
