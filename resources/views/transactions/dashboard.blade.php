@extends('layouts.app')

@section('content')
    @auth()
        <div class="flex justify-left">
            <div class="w-6/12 bg-red-500 p-4 rounded-lg justify-end ml-3 mb-3 mr-3 text-white text-bold">
                USER USD WALLET: $ {{ number_format((auth()->user()->wallet_balance_usd / 100), 2, ',', '') }}
            </div>
            <img src="{{ asset('logo/jackpot.jpg') }}">
    @endauth
            <div class="w-6/12 bg-red-500 p-4 rounded-lg justify-end ml-3 mb-3 mr-3 text-white text-bold">
                MERCHANT USD WALLET: $ {{ number_format((\App\Models\User::find(1)->wallet_balance_usd / 100), 2, ',', '') }}
            </div>
        </div>
            <div class="w-full bg-red-500 p-6 rounded-lg mb-3 mr-3 text-white text-bold">
                Request Payment
                <form action="{{ route('operate') }}" method="post">
                    @csrf
                    @auth()
                        <div class="flex w-full mb-4 mt-4 text-sm text-black mr-3">
                            <select name="type" id="type" class="gb-pink-300 mr-3 border-2 w-full rounded-lg @error('type') border-blue-500 @enderror">
                                <option value="">Type</option>
                                <option value="pix">PIX</option>
                                <option value="paylivre_wallet">Wallet</option>
                            </select>

                            <select name="operation" id="operation" class="gb-pink-300 mr-3 border-2 w-full rounded-lg @error('operation') border-blue-500 @enderror">
                                <option value="">Operation</option>
                                <option value="0">Deposit</option>
                                <option value="5">Withdrawal</option>
                            </select>

                            <label for="amount" class="sr-only">Amount</label>
                            <input type="text" name="amount" id="amount" placeholder="Amount" class="mr-3 gb-pink-300 border-2 w-full p-4 rounded-lg @error('amount') border-blue-500 @enderror">

                            <label for="email" class="sr-only">Email</label>
                            <input type="text" name="email" id="email" placeholder="Email" class="mr-3 gb-pink-300 border-2 w-full p-4 rounded-lg @error('email') border-blue-500 @enderror">

                            <label for="document_number" class="sr-only">CPF</label>
                            <input type="text" name="document_number" id="document_number" placeholder="CPF" class="mr-3 gb-pink-300 border-2 w-full p-4 rounded-lg @error('document_number') border-blue-500 @enderror">

                            <label for="pix_key" class="sr-only">Pix Key</label>
                            <input type="text" name="pix_key" id="pix_key" placeholder="Pix Key" class="mr-3 gb-pink-300 border-2 w-full p-4 rounded-lg @error('pix_key') border-blue-500 @enderror">

                            <select name="pix_key_type" id="pix_key_type" class="gb-pink-300 mr-3 border-2 w-full rounded-lg @error('pix_key_type') border-blue-500 @enderror">
                                <option value="">PIX Key Type</option>
                                <option value="0">CPF</option>
                                <option value="2">Phone</option>
                                <option value="3">Email</option>
                            </select>

                            <label for="user_paylivre_auth_token" class="sr-only">Pix Key</label>
                            <input type="text" name="user_paylivre_auth_token" id="user_paylivre_auth_token" placeholder="Paylivre Token" class="mr-3 gb-pink-300 border-2 w-full p-4 rounded-lg @error('user_paylivre_auth_token') border-blue-500 @enderror">
                        </div>
                        <div class="flex mr-3">
                            <button type="submit" name="integration" class="flex bg-blue-600 text-white px=4 py-3 rounded font-medium w-6/12 justify-center mr-3" value="gateway">PAYLIVRE GATEWAY</button>
                            <button type="submit" name="integration" class="ml-3 bg-blue-600 text-white px=4 py-3 rounded font-medium w-6/12 " value="api">PAYLIVRE API</button>
                        </div>
                    @endauth
                </form>
            </div>
    <div class=" justify-left text-center bg-red-500 p-6 rounded-lg mb-4 mr-3 mt-3 w-full">
        @if ($transactions->count())
            @foreach ($transactions as $transaction)
                <div class="mb-3 mt-3">
                    <div class="flex mb-3 mt-3">
                        <div class="justify-left bg-pink-100 p-3 rounded-lg ml-3 mr-3 w-full">
                            <div class="flex mb-3 mt-3 w-full">
                            @if ($transaction->transaction_type == \App\Models\TransactionType::DEPOSIT)
                                {{$transaction->id}} |
                                Deposit |
                                {{ $transaction->currency }} {{ number_format(($transaction->amount / 100), 2, ',', '') }} |
                                Status
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::NEW)
                                    New
                                @endif
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::PENDING)
                                    Pending
                                @endif
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::CANCELED)
                                    Cancelled
                                @endif
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::COMPLETED)
                                    Completed
                                @endif
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::EXPIRED)
                                    Expired
                                @endif
                                 | Last update {{$transaction->updated_at->diffForHumans()}} |
                            @else
                                {{$transaction->id}} |
                                Withdrawal |
                                {{ $transaction->currency }} {{ number_format(($transaction->amount / 100), 2, ',', '') }} |
                                Status
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::NEW)
                                    New
                                @endif
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::PENDING)
                                    Pending
                                @endif
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::CANCELED)
                                    Canceled
                                @endif
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::COMPLETED)
                                    Completed
                                @endif
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::EXPIRED)
                                    Expired
                                @endif
                                | Last update {{$transaction->updated_at->diffForHumans()}} |
                            @endif
                        <form action="{{route('operate')}}" method="post">
                            @csrf
                            @if ($transaction->transaction_status == \App\Models\TransactionStatus::PENDING || $transaction->transaction_status == \App\Models\TransactionStatus::NEW)
                            <div class="flex w-full text-sm text-black ml-3">
                                <label for="action" class="sr-only">Email</label>
                                <select name="action" id="action" class="gb-pink-300 mr-3 border-2 w-full rounded-lg @error('action')border-blue-500 @enderror">
                                    <option value="complete">Complete</option>
                                    <option value="cancel">Cancel</option>
                                    <option value="expire">Expire</option>
                                </select>
                                <div class="flex">
                                <input type="hidden" id="transaction_id" name="transaction_id" value="{{$transaction->id}}">
                                <button type="submit" name="operation" class="text-white bg-blue-500 rounded ml-3 font-medium" value="10">Callback Received From Paylivre</button>
                                </div>
                            </div>
                            @endif
                        </form>
                        </div>
                    </div>
                </div>
            @endforeach
            {{ $transactions->links() }}
        @else
            <div class="justify-left bg-pink-100 p-6 rounded-lg ml-3 mb-3 mr-3 mt-3 text-center text-pink-600">
                There are no transactions!
            </div>
        @endif
    </div>
@endsection
