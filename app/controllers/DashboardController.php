<?php

namespace App\Controllers;

use App\Models\AccountModel;
use App\Models\CardModel;
use App\Models\AppModel;
use App\Models\TransactionModel;

class DashboardController
{

    public function adminDashboard($request, $response)
    {

        // populate: can fetch one to many records of table i.e. cards based on fk=account_id
        $accounts = AccountModel::populate('cards')->populate('apps')->get();

        // foreach ($accounts as $account) {
        //     $account->cards = CardModel::where(['account_id' => $account->id])->get();
        //     $account->apps = AppModel::select()->where(['account_id' => $account->id])->get();
        // }

        $pending_txns = TransactionModel::link('account.user')->where(['status' => 'pending'])->sort_by('created_at', 'DESC')->get();

        return $response->view('admin.dashboard', ['user' => $request->user, 'accounts' => $accounts, 'pending_txns' => $pending_txns]);
    }
}
