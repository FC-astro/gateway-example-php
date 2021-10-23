@extends('layouts.app')

@section('content')
    <a href="{{ route('transactions') }}" class="p-3">Return to Dashboard</a>
    <div> </div>
    <div class="text-xl text-bold">Manufactured URL to redirect the customer to Paylivre's gateway: </div>
    <div class=" justify-left text-center bg-pink-300 text-pink-700 p-6 rounded-lg ml-3 mb-4 mr-3 mt-3">
        @dd($gateway);
    </div>
@endsection
