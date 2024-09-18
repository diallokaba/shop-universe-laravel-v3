<?php

namespace App\Repositories;

use App\Models\Client;

class ClientRepositoryImpl implements ClientRepositoryInterface{

    public function all()
    {
        return Client::all();
    }

    public function create(array $data)
    {
        return Client::create($data);
    }

    public function update(array $data, $id)
    {
        // TODO: Implement update() method.
        return Client::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
        return Client::destroy($id);
    }

    public function getByPhone($phone)
    {
        // TODO: Implement getByPhone() method.
        return Client::where('telephone', $phone)->first();
    }

    public function find($id)
    {
        // TODO: Implement getById() method.
        return Client::find($id);
    }

    public function clientWithHisAccount($id)
    {
        return Client::with('user')->findOrFail($id);
    }

    public function getClientWithHisDebts($id)
    {
        // TODO: Implement getClientWithHisDebts() method.
        return Client::with('dettes')->findOrFail($id);
    }
}