<?php

namespace App\Repositories;

interface ArticleRepositoryInterface
{
    public function all();
    public function create(array $articles);
    public function findOrFail($id);
    public function find($id);
    public function update($id, array $articles);
    public function delete($id);
    public function findByLibelle($libelle);
    public function findByEtat($value);
}