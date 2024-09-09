<?php

namespace App\Services;
use App\Repositories\ArticleRepositoryInterface;

class ArticleServiceImpl implements ArticleServiceInterface{

    private $articleRepository;

    public function __construct(ArticleRepositoryInterface $articleRepository){
        $this->articleRepository = $articleRepository;
    }
    public function all()
    {
        return $this->articleRepository->all();
    }

    public function create(array $articles)
    {
        // TODO: Implement create() method.
        return $this->articleRepository->create($articles);
    }

    public function find($id)
    {
        // TODO: Implement find() method.
        return $this->articleRepository->find($id);
    }

    public function update($id, array $articles)
    {
        // TODO: Implement update() method.
        return $this->articleRepository->update($id, $articles);
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
        return $this->articleRepository->delete($id);
    }

    public function findByLibelle($libelle)
    {
        // TODO: Implement findByLibelle() method.
        return $this->articleRepository->findByLibelle($libelle);
    }

    public function findByEtat($value)
    {
        // TODO: Implement findByEtat() method.
        return $this->articleRepository->findByEtat($value);
    }

}