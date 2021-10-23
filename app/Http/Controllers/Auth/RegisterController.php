<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;


class RegisterController extends Controller
{
    public function __construct() {
        $this->middleware(['guest']);
    }

    public function index(){
        return view('auth.register');
    }

    public function store(Request $request){

        $this->validate($request, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|confirmed',
            'tax_document' => 'required'
        ]);

        $user = User::create([
            'name'=> $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tax_document' => $request->tax_document,
            'wallet_balance_brl' => 0,
            'wallet_balance_usd' => 0
        ]);

        if ($user->id == 1){
            (new UserController)->setAdmin($user->id);
        }

        auth()->attempt($request->only('email','password'));

        return redirect()->route('transactions');
    }
}
