<?php

class WelcomeController {

    public function index($request, $response) {

        return $response->view('login');

    }

}