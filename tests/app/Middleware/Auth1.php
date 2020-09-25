<?php
declare(strict_types=1);

namespace App\Middleware;

use Minimal\Contracts\Middleware;

class Auth1 implements Middleware
{
    public function handle($req, $res, $next) : bool
    {
        return true;
    }
}