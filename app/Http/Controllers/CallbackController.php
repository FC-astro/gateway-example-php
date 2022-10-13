<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response as Res;
use Illuminate\Support\Str;
use function PHPUnit\Framework\arrayHasKey;

class CallbackController extends Controller
{
    public function receivePaylivreCallback(Request $request)
    {
        if (! $this->authenticatePaylivreCallback($request)) {
            return $this->respondUnauthorized();
        }

        $payload = $request->all();

        if (arrayHasKey('order_status_id',$payload)) {
            $transaction = Transaction::find($payload['partner_order_id']);
            if (in_array($transaction->transaction_status,[TransactionStatus::COMPLETED,TransactionStatus::CANCELLED,TransactionStatus::EXPIRED])) {
                return $this->respondBadRequest();
            }
            switch ($payload['order_status_id']) {
                case 0: // Status NEW
                case 1: // Status PENDING
                    // Request received successfully, finishing pre-processing and waiting for approval
                    $transaction->transaction_status = TransactionStatus::PENDING;
                    $transaction->notes = 'Waiting for payment';
                    $transaction->save();
                    break;
                case 2: // Status APPROVED -> Final Status
                    // Request approved successfully finish the transaction
                    $transactionService = new TransactionService($transaction);
                    $transactionService->completeTransaction($payload);
                    break;
                case 3: // Status CANCELED -> Final Status
                    // Unable to complete request, must be terminated unsuccessfully
                    $transactionService = new TransactionService($transaction);
                    $transactionService->cancelTransaction($payload);
                    break;
                case 4: // Status EXPIRED -> Final Status
                    // Payment was not finished in the permitted time, must be terminated unsuccessfully
                    $transactionService = new TransactionService($transaction);
                    $transactionService->expireTransaction($payload);
                    break;
                case 5: // Status INCOMPLETE
                    // User inputed the wrong credentials, allowed to retry up to 4 times
                    $transaction->transaction_status = TransactionStatus::PENDING;
                    $transaction->notes = 'Wrong Credentials';
                    $transaction->save();
                    break;
            }
            return $this->respondSuccess();
        }
        return $this->respondBadRequest();
    }

    private function authenticatePaylivreCallback(Request $callback):bool
    {
        $sentToken = $callback->header('X-Token');

        $reconstructedToken = hash_hmac('sha256', json_encode($callback->all()), env('PAYLIVRE_CALLBACK_TOKEN'));

        return $sentToken == $reconstructedToken;
    }

    public function respondRaw($data, array $headers = []): JsonResponse
    {
        $response = Res::json($data, $data['status_code'], $headers);

        return $response;
    }


    public function respondSuccess($data = null, string $message = 'OK', array $headers = []): JsonResponse
    {
        return $this->respondRaw([
            'status' => 'success',
            'status_code' => Response::HTTP_OK,
            'message' => $message,
            'data' => $data,
        ], $headers);
    }

    public function respondUnauthorized($data = null, string $message = 'Unauthorized', array $headers = []): JsonResponse
    {
        return $this->respondRaw([
            'status' => 'unauthorized',
            'status_code' => Response::HTTP_UNAUTHORIZED,
            'message' => $message,
            'data' => $data,
        ], $headers);
    }

    public function respondBadRequest($data = null, string $message = 'Bad Request', array $headers = []): JsonResponse
    {
        return $this->respondRaw([
            'status' => 'bad request',
            'status_code' => Response::HTTP_BAD_REQUEST,
            'message' => $message,
            'data' => $data,
        ], $headers);
    }

    public function simulateCallbackPayload($request)
    {
        $transaction = Transaction::find($request['transaction_id']);
        $user = User::find($transaction->user_id);

        if($request['payment_method'] == 'TED'){
            $paymentMethod = 0;
            $url = null;
        } elseif($request['payment_method'] == 'Billet') {
            $paymentMethod = 1;
            $url = 'https://shopline.itau.com.br/shopline/Itaubloqueto.asp?DC=H183X175W208T9O235O105C145N163F232O55R165L217I103Z205O6I136W92C103U208B14A7K144M46F96V241I243M255G163V205P221Q43Z15V6A116X1B54S28D50A88V38I186Y128D225U122K217H99S228X159U138Q126H27C227N171P170J58T191F124W242T234G86M192Q207K243N89C79F227S137K57I82X144I198Z142Y49T77P35G154L241P83L205I152T142H58Q100H123X39M34I18F189S195J166S20X238H169T209Y80R234Y193N179F146D216W123T199L192W80L11Z164O126W102C212S6Q159G104D247V236N187N184D123G155N183O6Y40H234P239X114X89T225S50S60C70Y156O80E30U69K97Z161W216T169B20D28G26L235T68D107C65W97D219O69K67D1C208F82N163G97E35V162X139F99W156V147P64Q131L31H206L145F240L229V68O97U206X58F232V131I141P12X46R194R254A101V123M204Z4F194R157N120O166I217W58F91B116K211K48Y149Z29Q51D232D131Q96H20E89T20J173X238K252F156Q216N108A83K56K17Y135Y195R126I210J60M196S181L61V221S26G205N102G244Y72O249C56W189Q227I88P0O108Q101K73R40H176A48Q185E0J73G98R246K213A209X128I143B69X184Q255O16N58A232N38P100V94T80R134M219M224F172R80N84Y232E98S237M142G224W253B198T23E231D54W146B64A53Q54P43Y133N165P40S98Y77O150X235I93D101U32P27X78Y166V247S95E248O217G86F74X135U23V125V59R168I221Y123P65M130V84Q61A100E149C182M247K99B227L174W209C67F135V196I43C130P13C159I143X79O193U68J19B48U150F43L59L120S0A76T129T159P110N100D215K217H173U136K247T118A13X88R91D131Q238N252D108M28W124T0M21E20O184I245P156S155E92R99O15Z177D45Z98J33Z22Y125N187Y97C170I25O128S95A232S169K138C163I69B223R16S13Q28A107L199Y31L207V90Q241G200K123Q167A127G174D207Z8X166R52Q35M243C71P121T136X221E83S76F215H206W160V103G72N29F78F158T240G35A222F118G92P22S173X155T133O202H212E20U172P114V160P17O113H5J23U24U113W7G87D5H172D252P182M210W16I86D192S207V208I138Z185H171Y73U207E35J244X61N251U41D59X35I';
        } elseif($request['payment_method'] == 'PIX') {
            $paymentMethod = 4;
            $url = '00020101021226580014BR.GOV.BCB.PIX0136b46359c8-3eaa-4b42-a3c6-0972a9c4a2265204000053039865406178.035802BR5912Pay Livrepix6014Belo Horizonte610830380403620905053154X63043697';
        } elseif($request['payment_method'] == 'Withdraw') {
            $paymentMethod = 5;
            $url = null;
        } elseif($request['payment_method'] == 'Wallet') {
            $paymentMethod = 6;
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

        if($transaction->transaction_type == TransactionType::DEPOSIT) {
            $depositId = $transaction->id + rand(1,100);
            $senderPartnerAccountId = $transaction->user_id;
            $depositTransactionId = $transaction->id + rand(1,100);
            $brokerDepositTransactionId = $transaction->id + rand(1,100);

            $withdrawalId = null;
            $receiverPartnerAccountId = null;
            $withdrawalTransactionId = null;
            $brokerWithdrawalTransactionId = null;
        } else {
            $withdrawalId = $transaction->id + rand(1,100);
            $receiverPartnerAccountId = $transaction->user_id;
            $withdrawalTransactionId = $transaction->id + rand(1,100);
            $brokerWithdrawalTransactionId = $transaction->id + rand(1,100);

            $depositId = null;
            $senderPartnerAccountId = null;
            $depositTransactionId = null;
            $brokerDepositTransactionId = null;
        }

        $taxTotal = intval($excangeTaxes ?? round($originalAmount*0.015));

        $callback =  [
            'order_type_id' => $paymentMethod,
            'order_status_id' => $orderStatus,
            'amount' => $amount,
            'original_amount' => $originalAmount,
            'tax_total' => $taxTotal,
            'currency' => 'BRL',
            'original_currency' => $transaction->currency,
            'deposit_id' => $depositId,
            'withdrawal_id' => $withdrawalId,
            'deposit_transaction_id' => $depositTransactionId,
            'withdrawal_transaction_id' => $withdrawalTransactionId,
            'exchange_transaction_id' => $exchangeTransactionId,
            'broker_deposit_transaction_id' => $brokerDepositTransactionId,
            'broker_withdrawal_transaction_id' => $brokerWithdrawalTransactionId,
            'partner_order_id' => $transaction->id,
            'sender_partner_account_id' => $senderPartnerAccountId,
            'receiver_partner_account_id' => $receiverPartnerAccountId,
            'url' => $url,
            'digitable_line'=> 'BilletUrl',
            'redirect_url' => 'http://127.0.0.1:8000/transactions',
            'completed_at' => $orderStatus == 2 ? Carbon::now()->toDateTimeString() : null,
            'created_at' => $transaction->created_at->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
            'verification_token' => Str::random(64),
            'id' => $transaction->id + rand(1,100),
            'order_status' => $orderStatus == 0 ? 'New' : ($orderStatus == 1 ? 'Pending' : ($orderStatus == 2 ? 'Approved' : ($orderStatus == 3 ? 'Cancelled' : ($orderStatus == 4 ? 'Expired' : 'Incomplete')))),
            'partner_id' => intval(env('MERCHANT_ID')),
            'user_id' => $transaction->user_id + 25,
            'user_email'=> $user->email,
            'decimals' => 2,
            'attempts' => []
            ];
        return $callback;
    }
}
