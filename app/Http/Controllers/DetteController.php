<?php

namespace App\Http\Controllers;

use App\Http\Requests\DetteRequest;
use App\Http\Requests\PaiementRequest;
use App\Services\DetteServiceInterface;
use Illuminate\Http\Request;

class DetteController extends Controller
{

    private DetteServiceInterface $detteService;
    public function __construct(DetteServiceInterface $detteService){
        $this->detteService = $detteService;
    }
    public function store(DetteRequest $request){
        $detteRequest = $request->only("client", "details_dette", "paiement");
        $dette = $this->detteService->create($detteRequest);
        return $dette;
    }

    public function index(Request $request){
        return $this->detteService->filter($request);
    }

    public function paiement($id, PaiementRequest $request){
        return $this->detteService->paiement($id, $request);
    }
}
