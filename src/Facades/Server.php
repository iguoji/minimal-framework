<?php
declare(strict_types=1);

namespace Minimal\Facades;

class Server extends Facade
{
    public static function getClass() : string
    {
        return 'server';
    }
}