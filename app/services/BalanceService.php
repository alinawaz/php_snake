<?php
namespace App\Services;

use App\Models\AccountModel;
use App\Models\TransactionModel;

class BalanceService
{

    public static function sync()
    {
        // Fetching all accounts 
        $accounts = AccountModel::all();
        foreach ($accounts as $account) {

            // total balance
            $balance = 0.0;

            // Fetching account transactions
            $transactions = TransactionModel::where(['account_id' => $account->id, 'status' => 'charged'])->get();
            foreach ($transactions as $trx) {
                if ($trx->type == 'credit') {
                    $balance += floatval($trx->amount);
                } else {
                    $balance -= floatval($trx->amount);
                }
            }

            // Updating account balance
            AccountModel::where(['id' => $account->id])->update(['balance' => $balance]);
        }
    }

    public static function syncAccount($account_id)
    {
        // total balance
        $balance = 0.0;

        // Fetching account transactions
        $transactions = TransactionModel::where(['account_id' => $account_id, 'status' => 'charged'])->get();
        foreach ($transactions as $trx) {
            if ($trx->type == 'credit') {
                $balance += floatval($trx->amount);
            } else {
                $balance -= floatval($trx->amount);
            }
        }

        // Updating account balance
        AccountModel::where(['id' => $account_id])->update(['balance' => $balance]);
    }
}
