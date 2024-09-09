<?php

namespace App\Services;

interface ClientServiceInterface
{
    public function all();
    public function create(array $data);
    public function update(array $data, $id);
    public function delete($id);
    public function getByPhone($phone);
    public function getById($id);
    public function clientWithUser($id);
}