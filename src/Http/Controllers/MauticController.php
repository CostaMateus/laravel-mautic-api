<?php

namespace Triibo\Mautic\Http\Controllers;

use Triibo\Mautic\Facades\Mautic;
use App\Http\Controllers\Controller;
use Triibo\Mautic\Models\MauticConsumer;

class MauticController extends Controller
{
    /**
     * Setup Applicaion.
     *
     * @return void
     */
    public function initiateApplication() : void
    {
        $message = "<h1>Mautic App Already Register</h1>";

        if ( MauticConsumer::count() === 0 )
        {
            Mautic::connection( "main" );
            $message = "<h1>Mautic App Successfully Registered</h1>";
        }

        echo $message;
    }
}
