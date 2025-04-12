<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the connections below you wish to use as
    | your default connection for all work. Of course, you may use many
    | connections at once using the manager class.
    |
    */
    "default" => "main",

    /*
    |--------------------------------------------------------------------------
    | Auth Type
    |--------------------------------------------------------------------------
    | Version of the auth can be OAuth2 or BasicAuth. OAuth2 is the default value.
    |
    */
    "version" => env( "MAUTIC_AUTH_VERSION", "OAuth2" ),

    /*
    |--------------------------------------------------------------------------
    | Mautic App Connections Setting
    |--------------------------------------------------------------------------
    |
    | Here are each of the connections setup for your application. Example
    | configuration has been included, but you may add as many connections as
    | you would like.
    |
    */
    "connections" => [
        "main" => [
            "version" => "OAuth2",
            "baseUrl" => env( "MAUTIC_BASE_URL" ),
            "clientKey" => env( "MAUTIC_PUBLIC_KEY" ),
            "clientSecret" => env( "MAUTIC_SECRET_KEY" ),
            "callback" => env( "MAUTIC_CALLBACK" ),
        ],
        "basic" => [
            "version" => "BasicAuth",
            "baseUrl" => env( "MAUTIC_BASE_URL" ),
            "username" => env( "MAUTIC_USERNAME" ),
            "password" => env( "MAUTIC_PASSWORD" ),
        ],
    ],

];
