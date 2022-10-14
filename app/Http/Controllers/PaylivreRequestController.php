<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use App\Services\PaylivreApiService;
use App\Services\PaylivreGatewayService;
use App\Services\TransactionService;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Response as Res;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaylivreRequestController extends Controller
{
    public function paylivreGatewayDeposit(Request $request){
        $this->validate($request, [
            'amount' => 'required|string',
            'type' => 'required',
            'user_paylivre_api_token' => Rule::requiredIf($request->input('type')==='paylivre_wallet'),
            'operation' => 'required',
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
            'transaction_status' => TransactionStatus::NEW,
            'amount' => $amount,
            'currency' => 'USD',
            'callback_url' => env('APP_URL').'/callback',
        ]);

        $gatewayUrl = (new PaylivreGatewayService)->generatePaylivreGatewayUrl($request, $depositTransaction);

        return $gatewayUrl;
    }

    public function paylivreGatewayWithdrawal(Request $request){
        $this->validate($request, [
            'amount' => 'required|string',
            'type' => 'required',
            'operation' => 'required',
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
            'transaction_status' => TransactionStatus::NEW,
            'amount' => $amount,
            'currency' => 'USD',
            'callback_url' => 'http://127.0.0.1:8000/'
        ]);

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

        $gatewayUrl = (new PaylivreGatewayService)->generatePaylivreGatewayUrl($request, $withdrawalTransaction);

        return $gatewayUrl;
    }

    public function paylivreApiDeposit(Request $request){
        $this->validate($request, [
            'amount' => 'required|string',
            'email' => 'required|email',
            'document_number' => 'required',
            'type' => 'required',
            'user_paylivre_api_token' => Rule::requiredIf($request->input('type')==='paylivre_wallet'),
            'operation' => 'required',
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
            'transaction_status' => TransactionStatus::NEW,
            'amount' => $amount,
            'currency' => 'USD',
            'callback_url' => env('APP_URL').'/callback',
        ]);

        $response = (new PaylivreApiService())->sendPaymentRequest($request, $depositTransaction);

        $this->handlePaylivreApiResponse($response, $withdrawalTransaction);

        return $response;
    }

    public function paylivreApiWithdrawal(Request $request){
        $this->validate($request, [
            'amount' => 'required|string',
            'email' => 'required|email',
            'document_number' => 'required',
            'type' => 'required',
            'pix_key_type' => Rule::requiredIf($request->input('type')==='pix'),
            'pix_key' => Rule::requiredIf($request->input('type')==='pix'),
            'operation' => 'required',
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
            'transaction_status' => TransactionStatus::NEW,
            'amount' => $amount,
            'currency' => 'USD',
            'callback_url' => 'http://127.0.0.1:8000/'
        ]);

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

        $response = (new PaylivreApiService)->sendPaymentRequest($request, $withdrawalTransaction);

        $this->handlePaylivreApiResponse($response, $withdrawalTransaction);

        return $response;
    }

    public function handlePaylivreApiResponse(Response $response, $transaction)
    {
        $content = json_decode($response,true);
        switch($response->status()){
            case Res::HTTP_CREATED:
                $transaction->transaction_status = TransactionStatus::PENDING;
                $transaction->save();
                break;
            case Res::HTTP_FORBIDDEN:
                $transactionService = new TransactionService($transaction);
                $transactionService->cancelTransaction($content);
                break;
            case Res::HTTP_BAD_REQUEST:
                $transactionService = new TransactionService($transaction);
                $transactionService->cancelTransaction($content);
                break;
            case Res::HTTP_FAILED_DEPENDENCY:
                $transactionService = new TransactionService($transaction);
                $transactionService->cancelTransaction($content);
                break;
            case Res::HTTP_UNPROCESSABLE_ENTITY:
                $transactionService = new TransactionService($transaction);
                $transactionService->cancelTransaction($content);
                break;
        }
    }
}
