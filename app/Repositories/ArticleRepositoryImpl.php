<?php

namespace App\Repositories;

use App\Models\Article;

class ArticleRepositoryImpl implements ArticleRepositoryInterface
{
    public function all()
    {
        return Article::all();
    }

    public function create(array $articles)
    {
        // TODO: Implement create() method.
        return Article::create($articles);
    }

    public function findOrFail($id)
    {
        // TODO: Implement find() method.
        return Article::findOrFail($id);
    }

    public function find($id)
    {
        // TODO: Implement find() method.
        return Article::find($id);
    }

    public function update($id, array $articles)
    {
        // TODO: Implement update() method.
        return Article::findOrFail($id)->update($articles);
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
        return Article::destroy($id);
    }

    public function findByLibelle($libelle)
    {
        // TODO: Implement findByLibelle() method.
        return Article::where('libelle', $libelle)->first();
    }

    public function findByEtat($value)
    {
        // TODO: Implement findByEtat() method.
        return Article::where('etat', $value)->get();
    }
}