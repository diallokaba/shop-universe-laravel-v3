<?php

namespace App\Services;

use App\Repositories\DetteRepositoryInterface;

class DetteServiceImpl implements DetteServiceInterface{

    private DetteRepositoryInterface $detteRepository;
    public function __construct(DetteRepositoryInterface $detteRepository){
        $this->detteRepository = $detteRepository;
    }

    public function all()
    {
        // TODO: Implement all() method.
    }

    public function create(array $data)
    {
        // TODO: Implement create() method.
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