<?php
declare(strict_types=1);

namespace Minimal\Facades;

use Minimal\Contracts\Facade;

class Db extends Facade
{
    public static function getClass() : string
    {
        return 'database';
    }
}