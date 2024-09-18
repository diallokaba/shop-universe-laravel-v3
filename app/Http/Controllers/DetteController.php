<?php

namespace App\Http\Controllers;

use App\Http\Requests\DetteRequest;
use App\Http\Requests\PaiementRequest;
use App\Models\Dette;
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

    public function getById($id){
        $dette = $this->detteService->find($id);

        if(!$dette){
            return response()->json(["message" => "La dette avec l'id " . $id . " n'existe pas."], 404);
        }else{
            return $dette;
        }
    }

    public function settledDebts(){
        
        $dettes = Dette::with(['paiements', 'client', 'articles'])->where('statut', 'solde')->get();
        return response()->json($dettes);
    }

    public function debtsDetails($id){
        $dette = $this->detteService->detbsWithDetails($id);

        if(!$dette){
            return response()->json(["message" => "La dette avec l'id " . $id . " n'existe pas."], 404);
        }else{
            return $dette;
        }
    }

    public function debtsWithAllpayments($id){
        $dette = $this->detteService->debtsWithPayments($id);

        if(!$dette){
            return response()->json(["message" => "La dette avec l'id " . $id . " n'existe pas."], 404);
        }else{
            return $dette;
        }
    }
}
