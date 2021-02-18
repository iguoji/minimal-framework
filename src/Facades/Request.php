<?php
declare(strict_types=1);

namespace Minimal\Facades;

class Request extends Facade
{
    public static function getClass() : string
    {
        return 'request';
    }
}