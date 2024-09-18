<?php 

namespace App\Repositories;

interface ClientRepositoryInterface
{
    public function all();
    public function create(array $data);
    public function update(array $data, $id);
    public function delete($id);
    public function getByPhone($phone);
    public function find($id);
    public function clientWithHisAccount($id);
    public function getClientWithHisDebts($id);
}