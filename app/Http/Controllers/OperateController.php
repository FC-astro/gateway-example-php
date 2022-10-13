<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionType;
use Illuminate\Http\Request;

class OperateController extends Controller
{
    public function index(Request $request) {
        switch($request->input('operation')) {
            case 0:
                $gatewayUrl = (new TransactionController())->deposit($request);
                //redirect($gatewayUrl);
                return view('transactions.gateway',[
                    'gateway' => $gatewayUrl
                ]);
            case 5:
                $gatewayUrl = (new TransactionController())->withdraw($request);
                //redirect($gatewayUrl);
                return view('transactions.gateway',[
                    'gateway' => $gatewayUrl
                ]);
            case 10:
                $data = [
                    'payment_method'=>'PIX',
                    'action'=>$request->action,
                    'transaction_id' => $request->transaction_id
                ];
                if ((Transaction::find($request->transaction_id))->transaction_type == TransactionType::WITHDRAWAL) {
                    $data['payment_method'] = 'Withdraw';
                }
                $payload = (new CallbackController())->simulateCallbackPayload($data);
                $request = new Request();
                $request->merge($payload);
                $response = (new CallbackController)->receivePaylivreCallback($request);
                //$response = Http::post('http://127.0.0.1:8000/callback',$payload);
                return view('transactions.callback',[
                    'callback' => [
                        'callback_received' => $request->all(),
                        'response_sent' => $response->getContent()
                    ]
                ]);
        }
    }
}
