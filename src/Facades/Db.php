<?php
declare(strict_types=1);

namespace Minimal\Facades;

class Db extends Facade
{
    public static function getClass() : string
    {
        return 'db';
    }
}