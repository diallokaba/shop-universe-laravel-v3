<?php

namespace App\Services;

interface DemandeServiceInterface{
    public function create(array $data);
    public function getDemandeWithFilterPossibility($request);
}