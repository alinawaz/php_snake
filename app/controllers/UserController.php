<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\AccountModel;

use Snake\Http\Request;
use Snake\Http\Response;

class UserController
{

    // @GET /admin/customers
    public function index(Request $request, Response $response)
    {

        $users = UserModel::populate('accounts')->get();
        
        return $response->view('admin.customer_listing', ['customers' => $users]);
    }

    // @POST /admin/customers/activate
    public function activate(Request $request, Response $response)
    {
        $user_id = $request->body->id;

        // Creating new pending account for user
        $new_account_number = "ACCT" . rand(10000,99999) . $user_id;
        AccountModel::create([
            'user_id' => $user_id,
            'type' => 'current',
            'account_number' => $new_account_number,
            'status' => 'pending'
        ]);
        
        // Activating user
        UserModel::where(['id' => $user_id])->update(['status' => 'active']);

        $response->redirect('/admin/customers');
    }

}