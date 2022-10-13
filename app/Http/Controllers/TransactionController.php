<?php

namespace App\Http\Controllers;

use App\Models\PaylivreGateway;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TransactionController extends Controller
{
    public function list() {
        $userId = auth()->user()->id;
        $transactions = Transaction::where('user_id','=', $userId)->orderBy('created_at','desc')->paginate(7);

        return view('transactions.dashboard',[
            'transactions' => $transactions
        ]);
    }

    public function deposit(Request $request){
        $this->validate($request, [
            'amount' => 'required|string',
            'email' => 'required|email',
            'document_number' => 'required'
        ]);

        if (str_contains($request->amount,',')) {
           $str = str_replace(',','',$request->amount);
           $amount = intval($str);
        } elseif (str_contains($request->amount,'.')) {
            $str = str_replace('.','',$request->amount);
            $amount = intval($str);
        } else {
            $amount = intval($request->amount);
            $amount *= 100;
        }

        $depositTransaction = $request->user()->transactions()->create([
            'transaction_type'=> TransactionType::DEPOSIT,
            'transaction_status' => TransactionStatus::PENDING,
            'amount' => $amount,
            'currency' => 'USD',
            'callback_url' => env('APP_URL').'/callback',
        ]);
        $depositTransaction->callback_url = $depositTransaction->callback_url.$depositTransaction->id.'/callback';

//        $gatewayUrl = (new \App\Models\PaylivreGateway)->generateOldGatewayUrl($request, $depositTransaction);
        $gatewayUrl = (new \App\Models\PaylivreGateway)->generatePaylivreGatewayUrl($request, $depositTransaction);

        return $gatewayUrl;
//        return redirect($gatewayUrl);
    }

    public function withdraw(Request $request){
        $this->validate($request, [
            'amount' => 'required|string',
            'email' => 'required|email',
            'document_number' => 'required'
        ]);

        if (str_contains($request->amount,',')) {
            $str = str_replace(',','',$request->amount);
            $amount = intval($str);
        } elseif (str_contains($request->amount,'.')) {
            $str = str_replace('.','',$request->amount);
            $amount = intval($str);
        } else {
            $amount = intval($request->amount);
            $amount *= 100;
        }

        $withdrawalTransaction = $request->user()->transactions()->create([
            'transaction_type'=> TransactionType::WITHDRAWAL,
            'transaction_status' => TransactionStatus::PENDING,
            'amount' => $amount,
            'currency' => 'USD',
            'callback_url' => 'http://127.0.0.1:8000/'
        ]);
        $withdrawalTransaction->callback_url = $withdrawalTransaction->callback_url.$withdrawalTransaction->id.'/callback';

        $user = User::find($withdrawalTransaction->user_id);
        $merchant = User::find(1);

        if ($withdrawalTransaction->amount <= $user->wallet_balance_usd && $withdrawalTransaction->amount <= $merchant->wallet_balance_usd) {
            $user->wallet_balance_usd = $user->wallet_balance_usd - $withdrawalTransaction->amount;
            $merchant->wallet_balance_usd = $merchant->wallet_balance_usd - $withdrawalTransaction->amount;
        } else {
            $withdrawalTransaction->transaction_status = TransactionStatus::CANCELLED;
            $withdrawalTransaction->save();
            return view('transactions.callback',[
                'callback' => 'Not enough funds for Withdrawal ('.$withdrawalTransaction->currency.' '.$withdrawalTransaction->amount.')'
            ]);
        }
        $user->save();
        $merchant->save();

        //$gatewayUrl = (new \App\Models\PaylivreGateway)->generateOldGatewayUrl($request, $withdrawalTransaction);
        $gatewayUrl = (new \App\Models\PaylivreGateway)->generatePaylivreGatewayUrl($request, $withdrawalTransaction);

        return $gatewayUrl;
        //return redirect($gatewayUrl);
    }
}
