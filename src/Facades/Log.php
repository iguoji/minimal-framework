<?php
declare(strict_types=1);

namespace Minimal\Facades;

use Minimal\Foundation\Facade;

class Log extends Facade
{
    public static function getClass() : string
    {
        return 'log';
    }
}