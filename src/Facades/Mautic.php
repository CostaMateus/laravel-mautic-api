<?php

namespace Triibo\Mautic\Facades;

use Illuminate\Support\Facades\Facade;

class Mautic extends Facade
{
    /**
     * Get Facade Accessor.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return "mautic";
    }
}
