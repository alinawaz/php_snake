<?php

/**
 * Web routes
 */

use Snake\Http\Router;

Router::get('/', 'AuthController@login');
Router::post('/auth', 'AuthController@authenticate');
Router::post('/register', 'AuthController@register');

Router::get('/transactions', 'TransactionController@index');

Router::middleware('auth', function($router) {

    $router::get('/logout', 'AuthController@logout');

    $router::get('/admin/dashboard', 'DashboardController@adminDashboard');
    $router::post('/admin/transactions/approve', 'TransactionController@approve');
    $router::post('/admin/transactions/decline', 'TransactionController@decline');
    

});