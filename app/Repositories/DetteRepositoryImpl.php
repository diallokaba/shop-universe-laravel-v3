<?php

namespace App\Repositories;

use App\Models\Dette;

class DetteRepositoryImpl implements DetteRepositoryInterface
{
    public function all()
    {
        return Dette::all();
    }

    public function create(array $data)
    {
        return Dette::create($data);
    }

    public function update(array $data, $id)
    {
        // TODO: Implement update() method.
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
    }

    public function find($id){
        return Dette::find($id);
    }

    public function findOrFail($id)
    {
        // TODO: Implement find() method.
        return Dette::findOrFail($id);
    }

    public function filter(){
        return Dette::query();
    }

    public function detbsWithDetails($id){
        return Dette::with('articles')->where('id', $id)->get();
    }

    public function debtsWithPayments($id){
        return Dette::with('paiements')->where('id', $id)->get();
    }
}