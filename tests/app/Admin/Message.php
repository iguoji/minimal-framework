<?php
declare(strict_types=1);

namespace App\Admin;

use Minimal\Facades\Log;

class Message
{
    public function send()
    {
        Log::info(__CLASS__ . ':::' . __FUNCTION__, func_get_args());
    }
}