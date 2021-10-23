<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function setAdmin($userId){
        if ($userId == 1) {
            $user = User::find($userId);
            $user->is_admin = 1;
            $user->save();
            return true;
        }
        return false;
    }

}
