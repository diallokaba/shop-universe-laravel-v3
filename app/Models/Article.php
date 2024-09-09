<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['reference', 'libelle', 'prix', 'quantite'];

    protected $hidden = ['created_at', 'updated_at'];

    public function dettes()
    {
        return $this->belongsToMany(Dette::class, 'article_dette')->withPivot('qteVente', 'prixVente');
    }

    public function scopeFilter($query, $request){

        if($request->has('disponible')){
            $value = strtolower($request->get('disponible'));
            if($value === 'oui'){
                $query->where('quantite', '>', 0);
            }elseif($value === 'non'){
                $query->where('quantite', '=', 0);
            }
        }

        if($request->has('libelle')){
            $value = strtolower($request->get('libelle'));
            $query->where('libelle', $value);
            // Optionnel : Utilisez 'like' pour une correspondance partielle
            // $query->where('libelle', 'like', '%'.$value.'%');
        }

        return $query;
    }
}
