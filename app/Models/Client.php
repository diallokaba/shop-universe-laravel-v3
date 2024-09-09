<?php

namespace App\Models;

use App\Observers\ClientObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    public function __construct(){
        
    }

    protected $fillable = ['surname','telephone', 'adresse', 'qrcode'];

    protected $hidden = ['created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    function dettes() {
        return $this->hasMany(Dette::class);
    }

    public function scopeFilter($query, $request){
        // Filtrer par comptes (avec ou sans utilisateur)
        if ($request->has('comptes')) {
            $value = strtolower($request->get('comptes'));
            
            if ($value === 'oui') {
                // Clients avec un compte utilisateur (user_id non null)
                $query->whereHas('user');
            } elseif ($value === 'non') {
                // Clients sans compte utilisateur (user_id null)
                $query->doesntHave('user');
            }
        }

         // Filtrer par activité du compte utilisateur
        if ($request->has('active')) {
            $value = strtolower($request->get('active'));
            
            if ($value === 'oui') {
                // Clients avec un compte utilisateur actif (active = 'OUI')
                $query->whereHas('user', function($query) {
                    $query->where('active', 'OUI');
                });
            } elseif ($value === 'non') {
                // Clients avec un compte utilisateur inactif (active != 'OUI')
                $query->whereHas('user', function($query) {
                    $query->where('active', 'NON');
                });
            }
        }

        return $query;
    }

    protected static function boot()
    {
        parent::boot();
        self::observe(ClientObserver::class);
    }

}
