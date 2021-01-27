<?php
declare(strict_types=1);

namespace Minimal\Facades;

class Config extends Facade
{
    public static function getClass() : string
    {
        return 'config';
    }
}