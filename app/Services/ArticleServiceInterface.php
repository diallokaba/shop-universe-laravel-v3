<?php

namespace App\Services;

interface ArticleServiceInterface
{
    public function all();
    public function create(array $articles);
    public function find($id);
    public function update($id, array $articles);
    public function delete($id);
    public function findByLibelle($libelle);
    public function findByEtat($value);
}