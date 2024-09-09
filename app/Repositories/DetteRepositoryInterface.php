<?php

namespace App\Repositories;

interface DetteRepositoryInterface
{
    public function all();
    public function create(array $data);
    public function update(array $data, $id);
    public function delete($id);
}