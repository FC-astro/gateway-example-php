<?php

namespace App\Services;


use App\Models\Transaction;
use App\Models\TransactionStatus;
use App\Models\TransactionType;
use App\Models\User;
use Carbon\Carbon;

class TransactionService
{
    private $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function completeTransaction($payload)
    {
        $this->transaction->updated_at = Carbon::now();
        $this->transaction->notes = json_encode($payload);
        $user = User::find($this->transaction->user_id);
        $merchant = User::find(1);
        if ($this->transaction->transaction_type == TransactionType::DEPOSIT && $this->transaction->transaction_status != TransactionStatus::COMPLETED){
            $user->wallet_balance_usd = $user->wallet_balance_usd + $this->transaction->amount;
            $merchant->wallet_balance_usd = $merchant->wallet_balance_usd + $this->transaction->amount;
        }
        $this->transaction->transaction_status = TransactionStatus::COMPLETED;
        $this->transaction->save();
        $user->save();
        $merchant->save();
    }

    public function cancelTransaction($payload = null)
    {
        $this->transaction->updated_at = Carbon::now();
        $this->transaction->notes = json_encode($payload);
        $user = User::find($this->transaction->user_id);
        $merchant = User::find(1);
        if ($this->transaction->transaction_type == TransactionType::WITHDRAWAL && $this->transaction->transaction_status != TransactionStatus::CANCELED && $this->transaction->transaction_status == TransactionStatus::PENDING){
            $user->wallet_balance_usd = $user->wallet_balance_usd + $this->transaction->amount;
            $merchant->wallet_balance_usd = $merchant->wallet_balance_usd + $this->transaction->amount;
        } elseif ($this->transaction->transaction_type == TransactionType::DEPOSIT && $this->transaction->transaction_status == TransactionStatus::COMPLETED) {
            $user->wallet_balance_usd = $user->wallet_balance_usd - $this->transaction->amount;
            $merchant->wallet_balance_usd = $merchant->wallet_balance_usd - $this->transaction->amount;
        }
        if ($this->transaction->transaction_status == TransactionStatus::PENDING){
            $this->transaction->transaction_status = TransactionStatus::CANCELED;
        }
        $this->transaction->save();
        $user->save();
        $merchant->save();
    }

    public function expireTransaction($payload)
    {
        $user = User::find($this->transaction->user_id);
        $merchant = User::find(1);
        if ($this->transaction->transaction_type == TransactionType::DEPOSIT && $this->transaction->transaction_status == TransactionStatus::COMPLETED){
            $user->wallet_balance_usd = $user->wallet_balance_usd - $this->transaction->amount;
            $merchant->wallet_balance_usd = $merchant->wallet_balance_usd - $this->transaction->amount;
        }
        $this->transaction->transaction_status = TransactionStatus::EXPIRED;
        $this->transaction->updated_at = Carbon::now();
        $this->transaction->notes = json_encode($payload);
        $this->transaction->save();
        $merchant->save();
        $user->save();
    }

    public function startTransaction()
    {
        $this->transaction->transaction_status = TransactionStatus::PENDING;
        $this->transaction->notes = 'Waiting for approval.';
        $this->transaction->save();

        if ($this->transaction->transaction_type == TransactionType::WITHDRAWAL) {
            $user = User::find($this->transaction->user_id);
            $merchant = User::find(1);
            if ($this->transaction->amount <= $user->wallet_balance_usd && $this->transaction->amount <= $merchant->wallet_balance_usd) {
                $user->wallet_balance_usd = $user->wallet_balance_usd - $this->transaction->amount;
                $merchant->wallet_balance_usd = $merchant->wallet_balance_usd - $this->transaction->amount;
                $user->save();
                $merchant->save();
            } else {
                $this->cancelTransaction();
            }
        }
    }
}
