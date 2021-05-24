<?php
declare(strict_types=1);

namespace Minimal\Facades;

use Minimal\Foundation\Facade;

class App extends Facade
{
    public static function getClass() : string
    {
        return 'app';
    }

    protected static function isShare() : bool
    {
        return true;
    }
}