<?php

/**
 * Middleware class for authentication
 * Handles user authentication and authorization
 */

class AuthMiddleware {

    public function handle($request, $next) {

        $user_model = loadModel('UserModel');

        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }else {
            $request->user = $user_model->find($_SESSION['user_id']);
        }

        return $next($request);

    }

}