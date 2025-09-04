<?php

namespace App\Controllers;

use App\Models\CardModel;

use Snake\Http\Request;
use Snake\Http\Response;

class CardController
{

    // public function index(Request $request, Response $response)
    // {

    //     $Cards = CardModel::populate('cards')->populate('apps')->where(['user_id' => $request->user->id])->get();

    //     return $response->view('customer.Card_listing', ['Cards' => $Cards]);
    // }

    public function create(Request $request, Response $response)
    {

        // Generate card details
        $card_number = str_pad(mt_rand(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
        $expiry_month = rand(1, 12);
        $expiry_year = date("Y") + rand(3, 5);
        $cvc = rand(100, 999);

        $card = CardModel::create([
            'card_number' => $card_number,
            'expiry_month' => $expiry_month,
            'expiry_year' => $expiry_year,
            'cvc' => $cvc,
            'status' => 'approved',
            'account_id' => $request->body->id
        ]);

        $folder = ($request->user->role == 'admin' ? 'admin' : 'customer');

        return $response->redirect('/' . $folder . '/' . 'accounts/' . $request->body->id);
    }
}
