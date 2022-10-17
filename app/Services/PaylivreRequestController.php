<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use App\Models\User;
use \Illuminate\Http\Client\Response;
use Illuminate\Http\Response as Res;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use function PHPUnit\Framework\arrayHasKey;

class PaylivreRequestController extends Controller
{
    public function paylivreGatewayDeposit(Request $request): string
    {
        $this->validate($request, [
            'amount' => 'required|string',
            'type' => 'required',
            'operation' => 'required',
        ]);

        $amount = $this->getIntAmountFromString($request->amount);

        $depositTransaction = $request->user()->transactions()->create([
            'transaction_type' => TransactionType::DEPOSIT,
            'transaction_status' => TransactionStatus::NEW,
            'amount' => $amount,
            'currency' => 'USD',
            'callback_url' => env('APP_URL').'/callback',
        ]);

        $gatewayUrl = $this->generatePaylivreGatewayUrl($request,$depositTransaction);
        return $gatewayUrl;
    }

    public function paylivreGatewayWithdrawal($request): string
    {
        $this->validate($request, [
            'amount' => 'required|string',
            'type' => 'required',
            'operation' => 'required',
        ]);

        $amount = $this->getIntAmountFromString($request->amount);

        $withdrawalTransaction = $request->user()->transactions()->create([
            'transaction_type' => TransactionType::WITHDRAWAL,
            'transaction_status' => TransactionStatus::NEW,
            'amount' => $amount,
            'currency' => 'USD',
            'callback_url' => env('APP_URL').'/callback',
        ]);

        $user = User::find($withdrawalTransaction->user_id);
        $merchant = User::find(1);

        $transactionService = new TransactionService($withdrawalTransaction);
        if ($withdrawalTransaction->amount <= $user->wallet_balance_usd && $withdrawalTransaction->amount <= $merchant->wallet_balance_usd) {
            $transactionService->startTransaction();
        } else {
            $transactionService->cancelTransaction($withdrawalTransaction);
            return view('transactions.callback',[
                'callback' => 'Not enough funds for Withdrawal ('.$withdrawalTransaction->currency.' '.$withdrawalTransaction->amount.')'
            ]);
        }
        $user->save();
        $merchant->save();

        $gatewayUrl = $this->generatePaylivreGatewayUrl($request,$withdrawalTransaction);
        return $gatewayUrl;
    }

    public function paylivreApiDeposit(Request $request)
    {
        $this->validate($request, [
            'amount' => 'required|string',
            'operation' => 'required',
            'type' => 'required',
            'email' => 'required|email',
            'document_number' => 'required',
            'user_paylivre_api_token' => Rule::requiredIf($request->input('type')==='paylivre_wallet'),
        ]);

        $amount = $this->getIntAmountFromString($request->amount);

        $depositTransaction = $request->user()->transactions()->create([
            'transaction_type'=> TransactionType::DEPOSIT,
            'transaction_status' => TransactionStatus::NEW,
            'amount' => $amount,
            'currency' => 'USD',
            'callback_url' => env('APP_URL').'/callback',
        ]);

        $response = $this->sendPaymentRequest($request, $depositTransaction);

        $this->handlePaylivreApiResponse($response, $depositTransaction);

        if ($request->input('type' == 1 and $request->input('operation' == 0))) {
            // Render QR Code
        }

        return $response;
    }

    public function paylivreApiWithdrawal(Request $request)
    {
        $this->validate($request, [
            'amount' => 'required|string',
            'type' => 'required',
            'operation' => 'required',
            'email' => 'required|email',
            'document_number' => 'required',
            'pix_key_type' => Rule::requiredIf($request->input('type')==='pix'),
            'pix_key' => Rule::requiredIf($request->input('type')==='pix'),
        ]);

        $amount = $this->getIntAmountFromString($request->amount);

        $withdrawalTransaction = $request->user()->transactions()->create([
            'transaction_type'=> TransactionType::WITHDRAWAL,
            'transaction_status' => TransactionStatus::NEW,
            'amount' => $amount,
            'currency' => 'USD',
            'callback_url' => 'http://127.0.0.1:8000/'
        ]);

        $user = User::find($withdrawalTransaction->user_id);
        $merchant = User::find(1);

        $transactionService = new TransactionService($withdrawalTransaction);
        if ($withdrawalTransaction->amount <= $user->wallet_balance_usd && $withdrawalTransaction->amount <= $merchant->wallet_balance_usd) {
            $transactionService->startTransaction();
        } else {
            $transactionService->cancelTransaction($withdrawalTransaction);
            return view('transactions.callback',[
                'callback' => 'Not enough funds for Withdrawal ('.$withdrawalTransaction->currency.' '.$withdrawalTransaction->amount.')'
            ]);
        }
        $user->save();
        $merchant->save();

        $response = $this->sendPaymentRequest($request, $withdrawalTransaction);

        $this->handlePaylivreApiResponse($response, $withdrawalTransaction);

        return $response;
    }

    public function getIntAmountFromString(string $string): int
    {
        if (str_contains($string,',')) {
            $str = str_replace(',','',$string);
            $amount = intval($str);
        } elseif (str_contains($string,'.')) {
            $str = str_replace('.','',$string);
            $amount = intval($str);
        } else {
            $amount = intval($string);
            $amount *= 100;
        }
        return $amount;
    }

    public function generatePaylivreGatewayUrl(Request $request, Transaction $transaction): string
    {
        $parameters['merchant_id'] = env('PAYLIVRE_MERCHANT_ID');

        $parameters['operation'] = $request->input('operation');
        $parameters['email'] = $request->input('email');
        $parameters['document_number'] = $request->input('document_number');
        $parameters['type'] = $request->input('type') == 'pix' ? 1 : 8;
        $parameters['api_token'] = $request->input('user_paylivre_auth_token');

        $parameters['merchant_transaction_id'] = $transaction->id;
        $parameters['amount'] = $transaction->amount;
        $parameters['currency'] = $transaction->currency;
        $parameters['account_id'] = $transaction->user->id;
        $parameters['callback_url'] = $transaction->callback_url;

        $parameters['auto_approve'] = true;

        if ($request->input('operation') == 5) {
            $parameters['pix_key'] = $request->input('pix_key');
            $parameters['pix_key_type'] = $request->input('pix_key_type');
        }

        $urlQueryParams = http_build_query($parameters);

        $signature = $this->buildSignature($urlQueryParams);

        $gatewayUrl = env('PAYLIVRE_GATEWAY_BASE_URL') . $urlQueryParams . '&signature=' . $signature;

        return $gatewayUrl;
    }

    public function buildSignature(string $urlParameters): string
    {
        $gatewayToken = env('PAYLIVRE_GATEWAY_TOKEN');
        $baseGatewayUrl = env('PAYLIVRE_GATEWAY_BASE_URL');

        $toHash = $gatewayToken . $baseGatewayUrl . $urlParameters;

        $hashed = Hash::make($toHash);
        $signature = base64_encode($hashed);

        return $signature;
    }

    public function sendPaymentRequest(Request $request, Transaction $transaction)
    {
        $payload = $this->generatePaylivreApiRequestPayload($request, $transaction);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])->post(env('PAYLIVRE_PAYMENT_REQUEST_API_ENDPOINT'),$payload);

        return $response;
    }

    public function generatePaylivreApiRequestPayload(Request $request, Transaction $transaction): array
    {
        $parameters['merchant_id'] = env('PAYLIVRE_MERCHANT_ID');

        $parameters['operation'] = $request->input('operation');
        $parameters['email'] = $request->input('email');
        $parameters['document_number'] = $request->input('document_number');
        $parameters['type'] = $request->input('type') == 'pix' ? 1 : 8;
        $parameters['api_token'] = $request->input('user_paylivre_auth_token');

        $parameters['merchant_transaction_id'] = $transaction->id;
        $parameters['amount'] = $transaction->amount;
        $parameters['currency'] = $transaction->currency;
        $parameters['account_id'] = $transaction->user->id;
        $parameters['callback_url'] = $transaction->callback_url;

        $parameters['auto_approve'] = true;

        if ($request->input('operation') == 5) {
            $parameters['pix_key'] = $request->input('pix_key');
            $parameters['pix_key_type'] = $request->input('pix_key_type');
        }

        $urlParameters = http_build_query($parameters);

        $signature = $this->buildSignature($urlParameters);

        $gatewayUrl = env('PAYLIVRE_GATEWAY_BASE_URL') . $urlParameters;

        $parameters['url'] = $gatewayUrl;
        $parameters['signature'] = $signature;

        if ($parameters['type'] == 8 && $parameters['operation'] == 0) {
            $parameters['login_email'] = $request->input('email');
            $parameters['api_token'] = $request->input('user_paylivre_api_token');
        }

        return $parameters;
    }

    public function handlePaylivreApiResponse(Response $response, $transaction)
    {
        $content = json_decode($response,true);
        switch($response->status()){
            case Res::HTTP_CREATED:
                if (arrayHasKey('order_status_id',$content['data'])) {
                    if($content['data']['order_status_id'] == 2) {
                        $transactionService = new TransactionService($transaction);
                        $transactionService->completeTransaction($content);
                    }
                } else {
                    $transaction->transaction_status = TransactionStatus::PENDING;
                }
                $transaction->notes = json_encode($content);
                $transaction->save();
                break;
            case Res::HTTP_FORBIDDEN:
            case Res::HTTP_BAD_REQUEST:
            case Res::HTTP_FAILED_DEPENDENCY:
            case Res::HTTP_UNPROCESSABLE_ENTITY:
                $transactionService = new TransactionService($transaction);
                $transactionService->cancelTransaction($content);
                break;
        }
        if ($content['data']['type'] == 1 and $content['data']['operation'] == 0) {
            // Render QR Code to user
            //renderQRCode($content['receivable_url']);
        }
    }
}
