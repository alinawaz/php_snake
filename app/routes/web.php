<?php

/**
 * Web routes
 */

use Snake\Http\Router;

Router::get('/', 'AuthController@login');
Router::post('/auth', 'AuthController@authenticate');
Router::get('/sign-up', 'AuthController@signUp');
Router::post('/register', 'AuthController@register');

Router::get('/transactions', 'TransactionController@index');

Router::middleware('auth', function ($router) {

    $router->get('/logout', 'AuthController@logout');

    /** Customer Flows */
    $router->group('/customer', function ($router) {
        $router->get('/dashboard', 'DashboardController@customerDashboard');
        $router->get('/accounts', 'AccountController@index');
    });

    /** Admin Flows */
    $router->group('/admin', function ($router) {
        $router->get('/dashboard', 'DashboardController@adminDashboard');
        $router->post('/transactions/approve', 'TransactionController@approve');
        $router->post('/transactions/decline', 'TransactionController@decline');

        $router->get('/customers', 'UserController@index');
        $router->post('/customers/activate', 'UserController@activate');

        $router->get('/customers/account/:id', 'AccountController@show');
    });
});
