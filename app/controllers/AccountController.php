<?php

namespace App\Controllers;

use App\Models\AccountModel;
use App\Services\BalanceService;

use Snake\Http\Request;
use Snake\Http\Response;

class AccountController
{

    public function index(Request $request, Response $response)
    {

        $accounts = AccountModel::populate('cards')->populate('apps')->where(['user_id' => $request->user->id])->get();

        return $response->view('customer.account_listing', ['accounts' => $accounts]);
    }

    public function show(Request $request, Response $response)
    {

        $account = AccountModel::populate('cards')->populate('apps')->where(['id' => $request->body->id])->first();
        
        $folder = ($request->user->role == 'admin' ? 'admin' : 'customer');

        return $response->view($folder . '.accounts.show', ['account' => $account]);
    }

    public function status(Request $request, Response $response) 
    {

        $account = AccountModel::where(['id' => $request->body->id])->update(['status' => $request->body->status]);

        $folder = ($request->user->role == 'admin' ? 'admin' : 'customer');

        return $response->view($folder . '.accounts.show', ['account' => $account]);

    }

    public function sync(Request $request, Response $response) 
    {

        BalanceService::syncAccount($request->body->id);

        $folder = ($request->user->role == 'admin' ? 'admin' : 'customer');

        return $response->redirect('/admin/dashboard');

    }


}