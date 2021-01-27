<?php
declare(strict_types=1);

namespace Minimal\Facades;

class Cache extends Facade
{
    public static function getClass() : string
    {
        return 'cache';
    }
}