<?php

namespace App\Controllers;

use App\Models\AccountModel;

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

        $accounts = AccountModel::populate('cards')->populate('apps')->where(['user_id' => $request->user->id, 'id' => $request->body->id])->first();

        return $response->view('admin.accounts.index', ['accounts' => $accounts]);
    }

}