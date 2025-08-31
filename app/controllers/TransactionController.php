<?php

class TransactionController
{


    public function approve($request, $response)
    {
        $txn_id = $request->body->id;
        model('TransactionModel')->where(['id' => $txn_id])->update(['status' => 'charged']);

        // Re-syncing all accounts
        service('BalanceService')->sync();

        header("Location: /admin/dashboard");
        exit;
    }

    public function decline($request, $response)
    {
        $txn_id = $request->body->id;
        model('TransactionModel')->where(['id' => $txn_id])->update(['status' => 'declined']);

        //Fetch transaction to get returned_account_id for crediting back to returned account
        $txn = model('TransactionModel')->where(['id' => $txn_id])->first();

        if ($txn && !empty($txn->returned_account_id)) {

            // Crediting back to returned account
            $returned_account_id = intval($txn->returned_account_id);
            $amount = floatval($txn->amount);
            model('TransactionModel')->create([
                'account_id' => $returned_account_id,
                'type' => 'credit',
                'amount' => $amount,
                'message' => 'Charge reversed as declined by issuer bank.',
                'status' => 'charged'
            ]);
        }

        // Re-syncing all accounts
        service('BalanceService')->sync();

        header("Location: /admin/dashboard");
        exit;
    }
}
