<?php

return function($route){
    $route->domain('192.168.2.12:8080', function() use($route){
        $route->group('open', '', function() use($route){
            $route->group('wechat', \App\Open\Wechat::class, function() use($route){
                $route->any('debug');
                $route->any('login');
            });
        });
    });
};