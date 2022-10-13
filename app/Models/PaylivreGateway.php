<?php

namespace App\Models;

use App\Http\Controllers\OperateController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PaylivreGateway extends Model
{
    const TYPE_PIX = 1;                              // 0001
    const TYPE_BILLET = 2;                           // 0010
    const TYPE_PIX_AND_BILLET = 3;                   // 0011
    const TYPE_TED = 4;                              // 0100
    const TYPE_PIX_AND_TED = 5;                      // 0101
    const TYPE_BILLET_AND_TED = 6;                   // 0110
    const TYPE_PIX_AND_BILLET_AND_TED = 7;           // 0111
    const TYPE_WALLET = 8;                           // 1000
    const TYPE_PIX_AND_WALLET = 9;                   // 1001
    const TYPE_BILLET_AND_WALLET = 10;               // 1010
    const TYPE_PIX_AND_BILLET_AND_WALLET = 11;       // 1011
    const TYPE_TED_AND_WALLET = 12;                  // 1100
    const TYPE_PIX_AND_TED_AND_WALLET = 13;          // 1101
    const TYPE_BILLET_AND_TED_AND_WALLET = 14;       // 1110
    const TYPE_ALL = 15;                             // 1111

    public function generatePaylivreGatewayUrl(Request $request, Transaction $transaction): string
    {
        $parameters['merchant_id'] = env('PAYLIVRE_MERCHANT_ID');
        $parameters['operation'] = $request->input('operation');
        $parameters['email'] = $request->input('email');
        $parameters['document_number'] = $request->input('document_number');
        $parameters['merchant_transaction_id'] = $transaction->id;
        $parameters['amount'] = $transaction->amount;
        $parameters['currency'] = $transaction->currency;
        $parameters['type'] = $this::TYPE_PIX;
        $parameters['account_id'] = $transaction->user->id;
        $parameters['auto_approve'] = true;
        $parameters['callback_url'] = 'http://127.0.0.1:8000/callback';
        $parameters['redirect_url'] = 'google.com';

        if ($request->input('operation') == TransactionType::WITHDRAWAL) {
            $parameters['pix_key'] = $request->input('pix_key');
            $parameters['pix_key_type'] = $request->input('pix_key_type');
        }

        $urlParameters = http_build_query($parameters);

        $signature = $this->buildSignature($urlParameters);

        $urlParameters = $urlParameters.'&signature='.$signature;

        $gatewayUrl = env('PAYLIVRE_GATEWAY_BASE_URL') . $urlParameters;

        return $gatewayUrl;
    }

    private function buildSignature(string $urlParameters):string
    {
        $baseUrl = env('PAYLIVRE_GATEWAY_BASE_URL');
        $gatewayToken = env('PAYLIVRE_GATEWAY_TOKEN');

        $toHash = $gatewayToken.$baseUrl.$urlParameters;

        $hashed = Hash::driver('bcrypt')->make($toHash);
        $signature = base64_encode($hashed);

        return $signature;
    }
}
