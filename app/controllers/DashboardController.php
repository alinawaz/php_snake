<?php

namespace App\Controllers;

use App\Models\AccountModel;
use App\Models\TransactionModel;

use Snake\Http\Request;
use Snake\Http\Response;

class DashboardController
{

    public function adminDashboard(Request $request, Response $response)
    {

        // populate: can fetch one to many records of table i.e. cards based on fk=account_id
        $accounts = AccountModel::populate('cards')->populate('apps')->get();

        // link: will get single recored of account_id inside of transactions, then take user_id from fetched account record to fetch user and link to accounts
        $pending_txns = TransactionModel::link('account.user')->where(['status' => 'pending'])->sort_by('created_at', 'DESC')->get();

        return $response->view('admin.dashboard', ['accounts' => $accounts, 'pending_txns' => $pending_txns]);
    }

    public function customerDashboard(Request $request, Response $response)
    {

        // populate: can fetch one to many records of table i.e. cards based on fk=account_id
        $accounts = AccountModel::populate('cards')->populate('apps')->where(['user_id' => $request->user->id])->get();

        return $response->view('customer.dashboard', ['accounts' => $accounts]);
    }
}
