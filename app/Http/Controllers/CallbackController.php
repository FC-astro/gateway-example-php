<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Util\Json;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class CallbackController extends Controller
{
    public function receivePaylivreCallback(Request $request)
    {
        $headers = $request->get('headers');
        if (! $this->authenticatePaylivreCallback($headers)) {
            return Response::HTTP_UNAUTHORIZED;
        }

        $payload = $request->get('data');

        if ($payload['order_status_id']);{
            return Response::HTTP_OK;
    }
    }

    private function authenticatePaylivreCallback($headers):bool
    {
        return true;
    }
    /*public function createCallback($request)
    {
        $payload = $this->simulateCallbackPayload($request);
        $callback = json_encode([
            'status' => 'success',
            'status_code' => 200,
            'message' => 'OK',
            'data' => $payload
        ]);
        return $callback;
    }

    public function simulateReceiveCallbackAndReturnSuccessAnswer($data){
        //$callback = $this->createCallback($data); //
        $this->handleCallback(json_decode($data));
        return ResponseAlias::HTTP_OK;
    }

    public function handleCallback($callback)
    {
        /*if($callback->data->order_status_id == 2) {
            (new TransactionController())->completeTransaction($callback);
        } elseif ($callback->data->order_status_id == 3) {
            (new TransactionController())->cancelTransaction($callback);
        } elseif ($callback->data->order_status_id == 4) {
            (new TransactionController())->expireTransaction($callback);
        }
    }

    public function simulateCallbackPayload($request)
    {
        $transaction = Transaction::find($request['transaction_id']);
        $user = User::find($transaction->user_id);

        if($request['payment_method'] == 'TED'){
            $paymentMethod = 0;
            $depositTaxes = 0;
            $url = null;
        } elseif($request['payment_method'] == 'Billet') {
            $paymentMethod = 1;
            $depositTaxes = 350;
            $url = 'https://shopline.itau.com.br/shopline/Itaubloqueto.asp?DC=H183X175W208T9O235O105C145N163F232O55R165L217I103Z205O6I136W92C103U208B14A7K144M46F96V241I243M255G163V205P221Q43Z15V6A116X1B54S28D50A88V38I186Y128D225U122K217H99S228X159U138Q126H27C227N171P170J58T191F124W242T234G86M192Q207K243N89C79F227S137K57I82X144I198Z142Y49T77P35G154L241P83L205I152T142H58Q100H123X39M34I18F189S195J166S20X238H169T209Y80R234Y193N179F146D216W123T199L192W80L11Z164O126W102C212S6Q159G104D247V236N187N184D123G155N183O6Y40H234P239X114X89T225S50S60C70Y156O80E30U69K97Z161W216T169B20D28G26L235T68D107C65W97D219O69K67D1C208F82N163G97E35V162X139F99W156V147P64Q131L31H206L145F240L229V68O97U206X58F232V131I141P12X46R194R254A101V123M204Z4F194R157N120O166I217W58F91B116K211K48Y149Z29Q51D232D131Q96H20E89T20J173X238K252F156Q216N108A83K56K17Y135Y195R126I210J60M196S181L61V221S26G205N102G244Y72O249C56W189Q227I88P0O108Q101K73R40H176A48Q185E0J73G98R246K213A209X128I143B69X184Q255O16N58A232N38P100V94T80R134M219M224F172R80N84Y232E98S237M142G224W253B198T23E231D54W146B64A53Q54P43Y133N165P40S98Y77O150X235I93D101U32P27X78Y166V247S95E248O217G86F74X135U23V125V59R168I221Y123P65M130V84Q61A100E149C182M247K99B227L174W209C67F135V196I43C130P13C159I143X79O193U68J19B48U150F43L59L120S0A76T129T159P110N100D215K217H173U136K247T118A13X88R91D131Q238N252D108M28W124T0M21E20O184I245P156S155E92R99O15Z177D45Z98J33Z22Y125N187Y97C170I25O128S95A232S169K138C163I69B223R16S13Q28A107L199Y31L207V90Q241G200K123Q167A127G174D207Z8X166R52Q35M243C71P121T136X221E83S76F215H206W160V103G72N29F78F158T240G35A222F118G92P22S173X155T133O202H212E20U172P114V160P17O113H5J23U24U113W7G87D5H172D252P182M210W16I86D192S207V208I138Z185H171Y73U207E35J244X61N251U41D59X35I';
        } elseif($request['payment_method'] == 'PIX') {
            $paymentMethod = 4;
            $depositTaxes = 0;
            $url = '00020101021226580014BR.GOV.BCB.PIX0136b46359c8-3eaa-4b42-a3c6-0972a9c4a2265204000053039865406178.035802BR5912Pay Livrepix6014Belo Horizonte610830380403620905053154X63043697';
        } elseif($request['payment_method'] == 'Withdraw') {
            $paymentMethod = 5;
            $depositTaxes = 0;
            $url = null;
        } elseif($request['payment_method'] == 'Wallet') {
            $paymentMethod = 6;
            $depositTaxes = 0;
            $url = null;
        }

        $action = $request['action'];
        if($action == 'complete'){
            $orderStatus = 2;
        } elseif($action == 'cancel') {
            $orderStatus = 3;
        } elseif($action == 'expire') {
            $orderStatus = 4;
        }
        $exchangeTaxes = intval(round($transaction->amount*0.0103));
        $amount = $transaction->amount * 5 + $exchangeTaxes;
        $originalAmount = $transaction->amount;
        $exchangeTransactionId = $transaction->id + rand(1,100);
        $quote = $transaction->id + rand(1,100);

        if($transaction->transaction_type == TransactionType::DEPOSIT) {
            $depositId = $transaction->id + rand(1,100);
            $senderPartnerAccountId = $transaction->user_id;
            $depositTransactionId = $transaction->id + rand(1,100);
            $brokerDepositTransactionId = $transaction->id + rand(1,100);
            $brokerDepositTaxes = 0;
            $bankAccountId = null;
            $documentValidationId = null;
            $urlHashCode = null;

            $withdrawalId = null;
            $receiverPartnerAccountId = null;
            $withdrawalTransactionId = null;
            $brokerWithdrawalTransactionId = null;
            $brokerWithdrawalTaxes = null;
        } else {
            $withdrawalId = $transaction->id + rand(1,100);
            $receiverPartnerAccountId = $transaction->user_id;
            $withdrawalTransactionId = $transaction->id + rand(1,100);
            $brokerWithdrawalTransactionId = $transaction->id + rand(1,100);
            $brokerWithdrawalTaxes = 0;
            $bankAccountId = $transaction->user_id + rand(1,100);
            $documentValidationId = $transaction->user_id + rand(1,100);
            $urlHashCode = Hash::make('Test');

            $depositId = null;
            $senderPartnerAccountId = null;
            $depositTransactionId = null;
            $brokerDepositTransactionId = null;
            $brokerDepositTaxes = null;
        }

        $callbackId = $transaction->id + rand(1,100);

        $taxTotal = intval($excangeTaxes ?? round($originalAmount*0.015));

        $callback =  [
            'id' => $transaction->id + rand(1,100), // Paylivre order(requested transaction) ID
            'partner_id' => intval(env('MERCHANT_ID')), // Our merchant user ID at Paylivre
            'user_id' => $transaction->user_id + 25, // The transaction's requester user ID at Paylivre (if registered)
            'order_type_id' => $paymentMethod, // Payment method:
            /* 0 = Wire Transfer Deposit.
             * 1 = Billet Deposit.
             * 4 = PIX Deposit.
             * 5 = Withdrawal request.
             * 6 = Deposit From Paylivre Wallet Balance.
            'order_status_id' => $orderStatus,
            /* 0 = NEW -> Order created.
            * 1 = PENDING -> Waiting for the user make the payment or for Paylivre to complete the withdrawal.
            * 2 = APPROVED -> Order completed successfully.
            * 3 = CANCELLED -> Some issue occurred with the payment and all intermediate transactions were cancelled, for withdrawals, the amount is returned to the origin.
            * 4 = EXPIRED -> The user failed to pay the deposit in an appropriate time frame.
            * ? = PROCESSING -> The payment was identified, and Paylivre is processing the intermediate transactions to complete the order.
            'notes' => null, // Cancellation reason, if any
            // request = Data from the initial request
            'request' => '{"order_type_id":'.$paymentMethod.',"original_amount":"'.$originalAmount.'"","original_currency":"'.$transaction->currency.'","amount":'.$amount.',"tax_total":'.$taxTotal.',"deposit_taxes":'.$depositTaxes.',"exchange_taxes":'.$exchangeTaxes.',"broker_deposit_taxes":'.$brokerDepositTaxes.',"ecommerce_taxes":0,"original_decimals":2,"decimals":2,"currency":"BRL","user_id":'.($transaction->user_id + 25).',"order_status_id":0,"partner_order_id":"'.$transaction->id.'","sender_partner_account_id":"'.$transaction->user_id.'","partner_id":'.env('MERCHANT_ID').',"quote_id":'.$quote.',"redirect_url":"http://127.0.0.1:8000/transactions"}',
            'amount' => $amount, // Amount paid by the user (amount requested + fees)
            'original_amount' => $originalAmount, // Amount requested by the user
            'tax_total' => $taxTotal, // fees
            'currency' => 'BRL',  // Currency of the payment (Always BRL)
            'original_currency' => $transaction->currency, // Currency of the request
            'decimals' => 2, // Number of decimals for the amount
            'original_decimals' => 2, // Number of decimals for the original_amount
            'response' => null,
            'deposit_id' => $depositId, // Paylivre's intermediate Deposit ID
            'withdrawal_id' => $withdrawalId, // Paylivre's intermediate Withdrawal ID
            'deposit_transaction_id' => $depositTransactionId, // Paylivre's intermediate Deposit transaction ID
            'withdrawal_transaction_id' => $withdrawalTransactionId, // Paylivre's intermediate Withdrawal transaction ID
            'exchange_transaction_id' => $exchangeTransactionId, // Paylivre's intermediate currency exchange transaction ID
            'broker_deposit_transaction_id' => $brokerDepositTransactionId, // Paylivre's transfer of the deposit amount to our merchant account
            'broker_withdrawal_transaction_id' => $brokerWithdrawalTransactionId, // Paylivre's transfer of the withdrawal amount from our merchant account
            'transaction_id' => null,
            'partner_order_id' => $transaction->id, // Our transaction ID
            'sender_partner_account_id' => $senderPartnerAccountId, // The requesting User ID on our system for a deposit
            'receiver_partner_account_id' => $receiverPartnerAccountId, // The requesting User ID on our system for a withdrawal
            'deposit_taxes' => $depositTaxes, // Emission fee for Billets
            'exchange_taxes' => $exchangeTaxes, // Currency exchange fees
            'broker_deposit_taxes' => $brokerDepositTaxes, // Paylivre Service fee for pay-ins
            'broker_withdrawal_taxes' => $brokerWithdrawalTaxes, // Paylivre Service fee for pay-outs
            'ecommerce_taxes' => 0,
            'quote_id' => $quote, // BRL to USD Quotation
            'url' => $url, // QR Code for PIX or the url for the Billet
            'redirect_url' => 'http://127.0.0.1:8000/transactions', // URL to redirect after operation
            'callback_id' => $callbackId, // Paylivre's ID for this callback message
            'bank_account_id' => $bankAccountId, // The ID for the User's bank account used for withdrawal at Paylivre (if registered)
            'document_validation_id' => $documentValidationId, // The ID for the User's document validatin at Paylivre (if registered)
            'url_hash_code' => $urlHashCode, // A parameter for the withdrawal gateway to check the status at Paylivre
            'completed_at' => $orderStatus == 2 ? Carbon::now() : null, // Date Time it was completed at Paylivre
            'created_at' => $transaction->created_at, // Date Time created at Paylivre
            'updated_at' => Carbon::now(), // Date Time last updated at Paylivre
            'user_email'=> $user->email, // The user email for the request
            'amount_received' => $originalAmount - $brokerDepositTaxes,
            'amount_received_currency' => $transaction->currency
        ];
        return $callback;
    }*/
}
