<?php

class AuthController
{

    private $user_model; 

    public function __construct()
    {
        $this->user_model = loadModel('UserModel');
    }

    public function login($request, $response)
    {

        if(!$request->body->username || !$request->body->password) {

            return $response->status(400)->json(['success' => false, 'error' => 'Missing credentials']);

        }

        $user = $this->user_model->select()->where(['username' => $request->body->username])->first();
        // var_dump($request->body->password, $user['password']);

        if (password_verify($request->body->password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            return $response->status(200)->json(['success' => true]);

        } else {

            return $response->status(401)->json(['success' => false, 'error' => 'Invalid username or password!']);

        }
        
    }
}
