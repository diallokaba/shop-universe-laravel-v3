<?php

namespace App\Repositories;

use App\Models\Dette;

class DetteRepositoryImpl implements DetteRepositoryInterface
{
    public function all()
    {
        // TODO: Implement all() method.
    }

    public function create(array $data)
    {
        Dette::create($data);
    }

    public function update(array $data, $id)
    {
        // TODO: Implement update() method.
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
    }
}