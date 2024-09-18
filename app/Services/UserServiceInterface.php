<?php

namespace App\Services;

interface UserServiceInterface
{
    public function getUserProfile($id);
    public function update($request);
    public function create(array $data);
    public function all();
    public function getUsers();
    public function find($id);
    public function delete($id);
    public function query($params);
    
}