<?php

namespace App\Repositories;

interface UserRepositoryInterface
{
    public function getUserProfile($id);
    public function update($id, array $data);
    public function create($data);
    public function all();
    public function find($id);
    public function delete($id);
    public function query();
    
}