<?php

namespace App\Controllers;

use App\Models\TransactionModel;
use App\Services\BalanceService;

use Snake\Http\Request;
use Snake\Http\Response;

class TransactionController
{

    public function index(Request $request, Response $response) {

        $result = TransactionModel::where(['id' => 1])->update(['status' => 'charged']);

        var_dump($result);exit;

    }

    public function approve(Request $request, Response $response)
    {
        $txn_id = $request->body->id;
        TransactionModel::where(['id' => $txn_id])->update(['status' => 'charged']);

        // Re-syncing all accounts
        BalanceService::sync();

        $response->redirect('/admin/dashboard');
    }

    public function decline(Request $request, Response $response)
    {
        $transaction = TransactionModel::where(['id' => $request->body->id])->update(['status' => 'declined']);

        if ($transaction && !empty($transaction->returned_account_id)) {

            // Crediting back to returned account
            TransactionModel::create([
                'account_id' => intval($transaction->returned_account_id),
                'type' => 'credit',
                'amount' => floatval($transaction->amount),
                'message' => 'Charge reversed as declined by issuer bank.',
                'status' => 'charged'
            ]);
        }

        // Re-syncing all accounts
        BalanceService::sync();

        $response->redirect('/admin/dashboard');
    }
}
