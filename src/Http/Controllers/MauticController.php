<?php

namespace Triibo\Mautic\Http\Controllers;

use App\Http\Controllers\Controller;
use Triibo\Mautic\Facades\Mautic;
use Triibo\Mautic\Models\MauticConsumer;

class MauticController extends Controller
{
    /**
     * Setup Applicaion.
     */
    public function initiateApplication()
    {
        $consumer = MauticConsumer::count();

        if ($consumer == 0) {
            Mautic::connection('main');
            echo '<h1>Mautic App Successfully Registered</h1>';
        } else {
            echo '<h1>Mautic App Already Register</h1>';
        }
    }
}
