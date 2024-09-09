<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dette extends Model
{
    use HasFactory;

    protected $fillable = ['montantTotal', 'montantVerser', 'montantRestant', 'client_id'];

    protected $hidden = ['updated_at'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_dette')->withPivot('qteVente', 'prixVente');
    }
}
