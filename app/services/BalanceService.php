<?php


class BalanceService
{

    public function sync()
    {
        // Fetching all accounts 
        $accounts = model('AccountModel')->all();
        foreach ($accounts as $account) {

            // total balance
            $balance = 0.0;

            // Fetching account transactions
            $transactions = model('TransactionModel')->where(['account_id' => $account->id, 'status' => 'charged']);
            foreach ($transactions as $trx) {
                if ($trx->type == 'credit') {
                    $balance += floatval($trx->amount);
                } else {
                    $balance -= floatval($trx->amount);
                }
            }

            // Updating account balance
            model('AccountModel')->where(['id' => $account->id])->update(['balance' => $balance]);
        }
    }

    public function syncAccount($account_id)
    {
        // total balance
        $balance = 0.0;

        // Fetching account transactions
        $transactions = model('TransactionModel')->where(['account_id' => $account_id, 'status' => 'charged']);
        foreach ($transactions as $trx) {
            if ($trx->type == 'credit') {
                $balance += floatval($trx->amount);
            } else {
                $balance -= floatval($trx->amount);
            }
        }

        // Updating account balance
        model('AccountModel')->where(['id' => $account_id])->update(['balance' => $balance]);
    }
}
