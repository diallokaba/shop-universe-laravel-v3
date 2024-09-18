<?php

namespace App\Repositories;

use App\Models\User;

class UserRepositoryImpl implements UserRepositoryInterface
{
    public function getUserProfile($id){

    }
    public function update($id, array $users){
        return User::where('id', $id)->update($users);
    }
    public function create($data){
        return User::create($data);
    }
    public function all(){
        return User::all();
    }
    
    public function find($id){
        return User::findOrFail($id);
    }
    public function delete($id){
        return User::destroy($id);
    }
    public function query(){
        return User::query();
    }
    
}