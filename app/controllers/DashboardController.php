<?php

class DashboardController
{

    public function adminDashboard($request, $response)
    {

        $accounts = model('AccountModel')->all();

        foreach ($accounts as $account) {
            $account->cards = model('CardModel')->where(['account_id' => $account->id])->get();
            $account->apps = model('AppModel')->select()->where(['account_id' => $account->id])->get();
        }

        $pending_txns = model('TransactionModel')->link('account.user')->where(['status' => 'pending'])->sort_by('created_at', 'DESC')->get();

        return $response->view('admin.dashboard', ['user' => $request->user, 'accounts' => $accounts, 'pending_txns' => $pending_txns]);
    }
}
