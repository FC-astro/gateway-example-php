@extends('layouts.app')

@section('content')
    <a href="{{ route('transactions') }}" class="p-3">Return to Dashboard</a>
    <div> </div>
    Response Sent To Paylivre: {{\Illuminate\Http\Response::HTTP_OK}} (HTTP_OK)
    <div> </div>
    <div class=" justify-left text-center bg-pink-300 text-pink-700 p-6 rounded-lg ml-3 mb-4 mr-3 mt-3">
        <div class="text-xl text-bold">Callback Received From Paylivre:</div>
        @dd($callback);
    </div>
@endsection
