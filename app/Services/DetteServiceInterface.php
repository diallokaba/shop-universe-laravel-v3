<?php

namespace App\Services;

interface DetteServiceInterface
{
    public function all();
    public function create(array $data);
    public function update(array $data, $id);
    public function delete($id);
}