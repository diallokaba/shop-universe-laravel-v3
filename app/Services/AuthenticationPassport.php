<?php

namespace App\Services;

use App\Traits\RestResponseTrait;
use Illuminate\Support\Facades\Auth;

class AuthenticationPassport implements AuthenticationServiceInterface{

    use RestResponseTrait;

    public function authenticate($credentials){
        if(Auth::attempt($credentials)){
            /** @var \App\Models\User $user */
            $user = Auth::user();
            //$user->token->revoke();
            $token = $user->createToken('authToken')->accessToken;

            return $token;
        }else {
            return null;
        }
    }

    public function logout(){
        auth()->user()->token()->revoke();
    }

}