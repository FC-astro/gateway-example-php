<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PaylivreGateway extends Model
{
    const TYPE_ALL = 15;
    const TYPE_PIX = 1;
    const TYPE_BILLET = 2;
    const TYPE_TED = 4;
    const TYPE_WALLET = 8;

    public function generateGatewayUrl(Request $request, Transaction $transaction) : string
    {

        $gatewayUrl = env('GATEWAY_BASE_URL');

        //merchant_id = our identification in Paylivre's system
        $urlParameters = 'merchant_id='.env('MERCHANT_ID');

        // operation = 0 forDeposit (Pay-in) or 1 for Withdrawal (Pay-out)
        if($transaction->transaction_type == TransactionType::DEPOSIT) {
            $urlParameters = $urlParameters . '&operation=0';
        } else {
            $urlParameters = $urlParameters . '&operation=5';
        }

        //email_address = the email of the requesting user
        $urlParameters = $urlParameters . '&email='.$request->user()->email;

        //document_number = the tax document number of the requesting user (numeric only)
        $urlParameters = $urlParameters . '&document='.$request->user()->tax_document;

        //partner_transaction_id = our transaction id
        $urlParameters = $urlParameters.'&partner_transaction_id='.$transaction->id;

        //amount = transactions amount to be received
        $urlParameters = $urlParameters.'&amount='.$transaction->amount;

        //currency = transaction final currency (of wallet which will receive the amount)
        $urlParameters = $urlParameters.'&currency='.'USD';

        //type = which payment methods are available to be selected
        $urlParameters = $urlParameters.'&type='.PaylivreGateway::TYPE_PIX;

        //account_id = the requesting user's ID in our system
        $urlParameters = $urlParameters.'&account_id='.$transaction->user_id;

        //callback_url = url which will receive a POST request with a JSON Payload, containing the transaction's status update from Paylivre
        $urlParameters = $urlParameters.'&callback_url='.$transaction->callback_url;

        //redirect_url = url the user will be redirected to after completing the request
        $urlParameters = $urlParameters.'&redirect_url='.'http://127.0.0.1:8000/transactions';

        //signature = hash of our gateway Token + the base url + the url parameters in orded
        $urlSignature = password_hash(env('GATEWAY_TOKEN').$gatewayUrl.$urlParameters,PASSWORD_ARGON2I);
        $urlParameters = $urlParameters.'&signature='.$urlSignature;

        return $gatewayUrl.$urlParameters;
    }

    public function generateOldGatewayUrl(Request $request, Transaction $transaction) : string
    {

        $gatewayUrl = 'https://playground.paylivre.com/checkout/';

        // operation = 0 forDeposit (Pay-in) or 1 for Withdrawal (Pay-out)
        if($transaction->transaction_type == TransactionType::DEPOSIT) {
            $gatewayUrl = $gatewayUrl . 'deposit/?';
        } else {
            $gatewayUrl = $gatewayUrl . 'withdrawal/?';
        }

        //email_address = the email of the requesting user
        $urlParameters = 'email_address='.$request->user()->email;

        //partner_transaction_id = our transaction id
        $urlParameters = $urlParameters.'&partner_transaction_id='.$transaction->id;

        //amount = transactions amount to be received
        $urlParameters = $urlParameters.'&amount='.$transaction->amount;

        //currency = transaction final currency (of wallet which will receive the amount)
        $urlParameters = $urlParameters.'&currency='.'USD';

        //type = which payment methods are available to be selected
        $urlParameters = $urlParameters.'&type=A';

        //account_id = the requesting user's ID in our system
        $urlParameters = $urlParameters.'&account_id='.$transaction->user_id;

        //callback_url = url which will receive a POST request with a JSON Payload, containing the transaction's status update from Paylivre
        $urlParameters = $urlParameters.'&callback_url='.$transaction->callback_url;

        //redirect_url = url the user will be redirected to after completing the request
        $urlParameters = $urlParameters.'&redirect_url='.'http://127.0.0.1:8000/transactions';

        //gateway_token
        $urlParameters = $urlParameters . '&gateway_token='.env('GATEWAY_TOKEN');

        $urlParameters = $urlParameters.'&first_name=Liver&last_name=test';

        return $gatewayUrl.$urlParameters;
    }
}
