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

        //merchant_id = the merchant's user id in Paylivre's system
        $urlParameters = 'merchant_id='.env('MERCHANT_ID');

        // operation = 0 for Deposit (Pay-in) or 1 for Withdrawal (Pay-out)
        if($transaction->transaction_type == TransactionType::DEPOSIT) {
            $urlParameters = $urlParameters . '&operation=0';
        } else {
            $urlParameters = $urlParameters . '&operation=5';
        }

        //email_address = the email of the requesting user
        $urlParameters = $urlParameters . '&email='.$request->user()->email;

        //document_number = the tax document number of the requesting user (numeric only)
        $urlParameters = $urlParameters . '&document_number='.$request->user()->tax_document;

        //merchant_transaction_id = this transaction's id in in the merchant system
        $urlParameters = $urlParameters.'&merchant_transaction_id='.$transaction->id;

        //amount = transactions amount to be received
        $urlParameters = $urlParameters.'&amount='.$transaction->amount;

        //currency = transaction final currency (of the wallet which will receive the amount) 'USD' or 'BRL'
        $urlParameters = $urlParameters.'&currency='.'USD';

        //type = which payment methods are available to be selected (Deposit) Described above. Multiple available types are the sum of the individual types
        //Example: PIX, Wallet and TED '&type='.(PaylivreGateway::TYPE_PIX + PaylivreGateway::TYPE_WALLET + PaylivreGateway::TYPE_TED);
        $urlParameters = $urlParameters.'&type='.PaylivreGateway::TYPE_PIX;

        //account_id = the requesting user's ID in the merchant's system
        $urlParameters = $urlParameters.'&account_id='.$transaction->user_id;
        
        //auto_approve = if the payment from Payivre to the customer should be automatically debited from the merchant's system and queued for processing (1 = TRUE)
        //or if the request needs to be manually approved by the merchant before debiting the amount and queueing for processing (0 = false)
        $urlParameters = $urlParameters.'&auto_approve='.'1';

        //callback_url = url which will receive a POST request with a JSON Payload, containing the transaction's information and its status updates from Paylivre
        $urlParameters = $urlParameters.'&callback_url='.$transaction->callback_url;

        //redirect_url = url the user will be redirected to after completing the request
        $urlParameters = $urlParameters.'&redirect_url='.'http://127.0.0.1:8000/transactions';

        //signature = base 64 encoding of the hash of the merchant's gateway Token + the base url + all the url parameters except the signature, on the same order as it wi be sent
        $urlSignature = base64_encode(password_hash(env('GATEWAY_TOKEN').$gatewayUrl.$urlParameters,PASSWORD_ARGON2I));
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
