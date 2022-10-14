<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class PaylivreApiService
{
    const TYPE_PIX = 1;
    const TYPE_BILLET = 2;
    const TYPE_TED = 4;
    const TYPE_WALLET = 8;

    public function sendPaymentRequest(Request $request, Transaction $transaction)
    {
        $payload = $this->generatePaylivreApiRequestPayload($request, $transaction);

        $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->post(env('PAYLIVRE_API_PAYMENT_ENDPOINT'),$payload);

        return $response;
    }

    public function generatePaylivreApiRequestPayload(Request $request, Transaction $transaction): array
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
        $parameters['redirect_url'] = 'https://www.google.com';

        if ($request->input('operation') == 5) {
            $parameters['pix_key'] = $request->input('pix_key');
            $parameters['pix_key_type'] = $request->input('pix_key_type');
        }

        $urlParameters = http_build_query($parameters);

        $signature = $this->buildSignature($urlParameters);

        $gatewayUrl = env('PAYLIVRE_GATEWAY_BASE_URL') . $urlParameters;

        $parameters['url'] = $gatewayUrl;
        $parameters['signature'] = $signature;

        if ($parameters['type'] == PaylivreApiService::TYPE_WALLET && $parameters['operation'] == 0) {
            $parameters['login_email'] = $request->input('email');
            $parameters['api_token'] = $request->input('user_paylivre_api_token');
        }

        return $parameters;
    }

    private function buildSignature(string $urlParameters):string
    {
        $baseUrl = env('PAYLIVRE_GATEWAY_BASE_URL');
        $gatewayToken = env('PAYLIVRE_GATEWAY_TOKEN');

        $toHash = $gatewayToken.$baseUrl.$urlParameters;

        $hashed = Hash::make($toHash);
        $signature = base64_encode($hashed);

        return $signature;
    }
}