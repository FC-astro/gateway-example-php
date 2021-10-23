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
                Transaction
                <form action="{{ route('operate') }}" method="post">
                    @csrf
                    @auth()
                        <div class="flex w-full mb-4 mt-4 text-sm text-black mr-3">
                            <label for="email" class="sr-only">Email</label>
                            <input type="text" name="email" id="email" placeholder="Your email" class="mr-3 gb-pink-300 border-2 w-full p-4 rounded-lg @error('email')border-blue-500 @enderror" value="{{old('email')}}">

                            <label for="tax_document" class="sr-only">Tax Document Number</label>
                            <input type="text" name="tax_document" id="tax_document" placeholder="Your tax document number" class="mr-3 gb-pink-300 border-2 w-full p-4 rounded-lg @error('tax_document')border-blue-500 @enderror"value="{{old('tax_document')}}">

                            <label for="amount" class="sr-only">Amount</label>
                            <input type="text" name="amount" id="amount" placeholder="Amount" class="mr-3 gb-pink-300 border-2 w-full p-4 rounded-lg @error('amount')border-blue-500 @enderror">
                        </div>

                        <div class="flex mr-3">
                            <button type="submit" name="deposit" class="flex bg-blue-600 text-white px=4 py-3 rounded font-medium w-6/12 justify-center mr-3" value="deposit">PAYLIVRE DEPOSIT GATEWAY</button>
                            <button type="submit" name="withdrawal" class="ml-3 bg-blue-600 text-white px=4 py-3 rounded font-medium w-6/12 " value="withdrawal">PAYLIVRE WITHDRAWAL GATEWAY</button>
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
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::PENDING)
                                    Pending
                                @endif
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::CANCELLED)
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
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::PENDING)
                                    Pending
                                @endif
                                @if ($transaction->transaction_status == \App\Models\TransactionStatus::CANCELLED)
                                    Cancelled
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
                            @if ($transaction->transaction_status == \App\Models\TransactionStatus::PENDING)
                            <div class="flex w-full text-sm text-black ml-3">
                                <label for="action" class="sr-only">Email</label>
                                <select name="action" id="action" class="gb-pink-300 mr-3 border-2 w-full rounded-lg @error('action')border-blue-500 @enderror">
                                    <option value="complete">Complete</option>
                                    <option value="cancel">Cancel</option>
                                    @if ($transaction->transaction_type == \App\Models\TransactionType::DEPOSIT)
                                    <option value="expire">Expire</option>
                                    @endif
                                </select>
                                <div class="flex">
                                <input type="hidden" id="transaction_id" name="transaction_id" value="{{$transaction->id}}">
                                <button type="submit" name="callback" class="text-white bg-blue-500 rounded ml-3 font-medium" value="callback">Simulate Callback</button>
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
