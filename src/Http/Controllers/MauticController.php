<?php

namespace CostaMateus\Mautic\Http\Controllers;

use Illuminate\Routing\Controller;
use CostaMateus\Mautic\Facades\Mautic;
use CostaMateus\Mautic\Models\MauticConsumer;

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
