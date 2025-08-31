<?php

/**
 * Web routes
 */

Router::get('/', 'WelcomeController@index');
Router::post('/auth', 'AuthController@login');

Router::middleware('AuthMiddleware', function($router) {


    $router::get('/admin/dashboard', 'DashboardController@adminDashboard');
    $router::post('/admin/transactions/approve', 'TransactionController@approve');
    $router::post('/admin/transactions/decline', 'TransactionController@decline');
    

});