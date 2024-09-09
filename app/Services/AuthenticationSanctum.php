<?php

namespace App\Services;

use App\Enums\StatusResponseEnum;
use App\Models\User;
use App\Traits\RestResponseTrait;
use Illuminate\Support\Facades\Hash;

class AuthenticationSanctum implements AuthenticationServiceInterface{

    use RestResponseTrait;
    
    public function authenticate($credentials){
        if(auth()->attempt($credentials)){
            /** @var \App\Models\User $user */
            $user = auth()->user();
            //$user->token->revoke();
            $token = $user->createToken('authToken')->plainTextToken;
            return $token;
        }
        
        return null;
        
    }

    public function logout(){
        //logout user with sanctum
        auth()->user()->tokens()->delete();
    }
}