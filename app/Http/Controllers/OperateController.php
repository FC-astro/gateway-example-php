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
                if ($request->input('integration') == 'gateway') {
                    $gatewayUrl = (new PaylivreRequestController())->paylivreGatewayDeposit($request);
                    return view('transactions.gateway',[
                        'gateway' => $gatewayUrl
                    ]);
                }
                if ($request->input('integration') == 'api') {
                    $response = (new PaylivreRequestController())->paylivreApiDeposit($request);
                    $content = json_decode($response,true);
                    return view('transactions.api',[
                        'api' => $content
                    ]);
                }
            case 5:
                if ($request->input('integration') == 'gateway') {
                    $gatewayUrl = (new PaylivreRequestController())->paylivreGatewayWithdrawal($request);
                    return view('transactions.gateway',[
                        'gateway' => $gatewayUrl
                    ]);
                }
                if ($request->input('integration') == 'api') {
                    $response = (new PaylivreRequestController())->paylivreApiWithdrawal($request);
                    $content = json_decode($response,true);
                    return view('transactions.api',[
                        'api' => $content
                    ]);
                }
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
                $XToken = hash_hmac('sha256', json_encode($payload), env('PAYLIVRE_CALLBACK_TOKEN'));

                $request->headers->set('X-Token',$XToken);
                $response = (new CallbackController)->receivePaylivreCallback($request);

                //$response = Http::withHeaders([
                //                    'Accept' => 'application/json',
                //                    'Content-Type' => 'application/json',
                //                ])->post('http://127.0.0.1:8000/callback',$payload);

                return view('transactions.callback',[
                    'callback' => [
                        'callback_received' => $request->all(),
                        'response_sent' => $response->getContent()
                    ]
                ]);
        }
    }
}
