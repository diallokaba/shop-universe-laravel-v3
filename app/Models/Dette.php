<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Dette extends Model
{
    use HasFactory;

    protected $fillable = ['montant', 'client_id'];

    protected $hidden = ['updated_at'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'details_dette')->withPivot('qteVente', 'prixVente');
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    public function getDetailsDette()
    {
        // Récupérer les détails de la dette à partir de la table "details_dette"
        return DB::table('details_dette')
            ->join('articles', 'details_dette.article_id', '=', 'articles.id')
            ->where('details_dette.dette_id', $this->id)
            ->select('details_dette.*', 'articles.libelle') // Ajouter les champs nécessaires ici
            ->get();
    }
}
