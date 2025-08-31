<?php

/**
 * Middleware class for authentication
 * Handles user authentication and authorization
 */

namespace App\Middlewares;

use App\Models\UserModel;

class AuthMiddleware {

    public function handle($request, $next) {

        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }else {
            $request->user = UserModel::find($_SESSION['user_id']);
        }

        return $next($request);

    }

}