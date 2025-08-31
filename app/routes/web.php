<?php

/**
 * Web routes
 */

use Snake\Http\Router;

Router::get('/', 'WelcomeController@index');
Router::post('/auth', 'AuthController@login');

Router::get('/transactions', 'TransactionController@index');

Router::middleware('auth', function($router) {


    $router::get('/admin/dashboard', 'DashboardController@adminDashboard');
    $router::post('/admin/transactions/approve', 'TransactionController@approve');
    $router::post('/admin/transactions/decline', 'TransactionController@decline');
    

});