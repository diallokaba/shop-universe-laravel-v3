<?php

namespace App\Services;

interface DetteServiceInterface
{
    public function all();
    public function create(array $data);
    public function update(array $data, $id);
    public function delete($id);
    public function filter($request);
    public function paiement($id, $request);
    public function find($id);
    public function detbsWithDetails($id);
    public function debtsWithPayments($id);

}