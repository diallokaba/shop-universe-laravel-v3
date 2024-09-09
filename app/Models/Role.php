<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['nomRole'];

    protected $hidden = ['created_at', 'updated_at'];

    function users() {
        return $this->hasMany(User::class);
    }
}
