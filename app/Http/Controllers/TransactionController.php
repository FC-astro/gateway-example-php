<?php

namespace App\Http\Controllers;

use App\Models\Transaction;

class TransactionController
{
    public function list() {
        $userId = auth()->user()->id;
        $transactions = Transaction::where('user_id','=', $userId)->orderBy('created_at','desc')->paginate(7);

        return view('transactions.dashboard',[
            'transactions' => $transactions
        ]);
    }
}
