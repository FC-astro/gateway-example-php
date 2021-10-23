<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionType;
use Illuminate\Http\Request;

class OperateController extends Controller
{
    public function index(Request $request) {
        if($request->deposit) {
            $gatewayUrl = (new TransactionController())->deposit($request);
            //redirect($gatewayUrl);
            return view('transactions.gateway',[
                'gateway' => $gatewayUrl
            ]);
        }
        if($request->withdrawal) {
            $gatewayUrl = (new TransactionController())->withdraw($request);
            //redirect($gatewayUrl);
            return view('transactions.gateway',[
                'gateway' => $gatewayUrl
            ]);
        }
        if($request->callback) {
            $data = [
                'payment_method'=>'PIX',
                'action'=>$request->action,
                'transaction_id' => $request->transaction_id
            ];
            if ((Transaction::find($request->transaction_id))->transaction_type == TransactionType::WITHDRAWAL)
            {
                $data['payment_method'] = 'Withdraw';
            }
            $response = (new CallbackController())->simulateReceiveCallbackAndReturnSuccessAnswer($data);
            return view('transactions.callback',[
                'callback' => json_decode($response)
            ]);
        }
    }
}
