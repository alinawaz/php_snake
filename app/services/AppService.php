<?php

namespace App\Services;

use App\Middlewares\AuthMiddleware;

class AppService
{

    /**
     * Register your middlewares
     */
    public static function middlewares()
    {

        return ['auth' => AuthMiddleware::class];
    }
}
