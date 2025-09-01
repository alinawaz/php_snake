<?php

namespace App\Controllers;

use App\Models\UserModel;
use Snake\Http\Session;

use Snake\Http\Request;
use Snake\Http\Response;

class AuthController
{

    public function login(Request $request, Response $response)
    {

        return $response->view('login');
    }

    public function authenticate(Request $request, Response $response)
    {

        $validation = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validation->errors()) {
            return $response->status(400)->json($validation->errors());
        }

        $user = UserModel::where(['username' => $request->body->username])->first();

        if (password_verify($request->body->password, $user->password)) {

            Session::put('user_id', $user->id);
            Session::put('username', $user->username);
            Session::put('role', $user->role);

            return $response->status(200)->json(['success' => true]);
        } else {

            return $response->status(401)->json(['success' => false, 'error' => 'Invalid username or password!']);
        }
    }

    public function register(Request $request, Response $response)
    {
        $validation = $request->validate([
            'name' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validation->errors()) {
            return $response->status(400)->json($validation->errors());
        }

        // Check if username already exists
        $existing = UserModel::where(['username' => $request->body->username]);
        if ($existing) {
            return $response->status(409)->json(['success' => false, 'error' => 'Username already taken']);
        }

        $user = UserModel::create([
            'name' => $request->body->name,
            'username' => $request->body->username,
            'password' => $request->body->password
        ]);

        if ($user) {
            return $response->status(200)->json(['success' => true, 'message' => "Signup successful!"]);
        }
        return $response->status(500)->json(['success' => false, 'error' => 'Signup failed']);
    }

    public function logout(Request $request, Response $response)
    {
        Session::flush();
        return $response->redirect('/');
    }
}
