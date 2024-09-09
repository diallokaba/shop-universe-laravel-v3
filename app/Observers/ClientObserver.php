<?php

namespace App\Observers;

use App\Events\ClientEvent;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class ClientObserver
{
    /**
     * Handle the Client "created" event.
     */

    public function created(Client $client): void
    {
        $data = request()->all();
        //$clientRequest = $request->only('surname','adresse','telephone', 'user');
        $roleId = $data['user']['role']['id'] ?? null;
        if (!$roleId) {
            throw new Exception('Le rôle de l\'utilisateur est manquant.');
        }
    
        $role = Role::find($roleId);
        if (!$role) {
            throw new Exception('Rôle non trouvé avec l\'ID: ' . $roleId);
        }

        $photoUrl = 'https://cdn-icons-png.flaticon.com/128/17346/17346780.png';
        if (isset($data['user']['photo']) && $data['user']['photo'] instanceof UploadedFile) {
            $photoUrl = $data['user']['photo']->getRealPath();
        }

        $user = new User();
        $user->login = $data['user']['login'];
        $user->password = Hash::make($data['user']['password']);
        $user->role_id = $role->id;
        $user->nom = $data['user']['nom'];
        $user->photo = $photoUrl;
        $user->prenom = $data['user']['prenom'];
        $user->save();

        $client->user_id = $user->id;
        $client->save();

        event(new ClientEvent($client, $user));
    }
}
