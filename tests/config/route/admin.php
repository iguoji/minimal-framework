<?php

return function($route){
    // 后台管理
    $route->addGroup('/admin', function($route){
        $route->addRoute('GET', '/message', [\App\Admin\Message::class, 'send']);
    });
};