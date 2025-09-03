<?php

/**
 * Middleware class for authentication
 * Handles user authentication and authorization
 */

namespace App\Middlewares;

use App\Models\UserModel;

class AuthMiddleware
{

    public function handle($request, $next)
    {

        // Check session
        if (!isset($_SESSION['user_id'])) {
            header('Location: /');
            exit();
        } else {
            $request->user = UserModel::find($_SESSION['user_id']);

            // Checking role
            // dd($request->user->role);
            // dD(!str_contains($request->path(), '/admin') , ' && ', $request->path() != '/logout');
            if ($request->user->role == 'admin') {
                if (!str_contains($request->path(), '/admin') && $request->path() != '/logout') {
                    header('Location: /admin/dashboard');
                    exit();
                }
            }else if ($request->user->role == 'user') {
                // dd($request->path());
                if (!str_contains($request->path(), '/customer') && $request->path() != '/logout') {
                    header('Location: /customer/dashboard');
                    exit();
                }
            }
        }

        return $next($request);
    }
}
