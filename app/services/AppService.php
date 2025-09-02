<?php

namespace App\Services;

use App\Middlewares\AuthMiddleware;
use App\Middlewares\AdminMiddleware;
use App\Middlewares\CustomerMiddleware;

class AppService
{

    /**
     * Register your middlewares
     */
    public static function middlewares()
    {

        return [
            'auth' => AuthMiddleware::class,
        ];
    }
}
