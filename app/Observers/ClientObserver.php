<?php

namespace App\Observers;

use App\Events\ClientEvent;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use App\Rules\CustomPasswordRule;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ClientObserver
{
    /**
     * Handle the Client "created" event.
     */

    public function created(Client $client): void
    {
        $data = request()->all();
        $user = null;
        if(!empty($data['user']['nom']) || !empty($data['user']['prenom']) || !empty($data['user']['login']) || !empty($data['user']['photo']) || !empty($data['user']['password'])) {
            $validator = $this->validateData($data['user']);

            if ($validator->fails()) {
                throw new Exception('Erreur de validation des données utilisateur : ' . $validator->errors()->first());
            }
            
            $roleId = $data['user']['role']['id'] ?? null;
            if (!$roleId) {
                throw new Exception('Le rôle de l\'utilisateur est manquant.');
            }
        
            $role = Role::find($roleId);
            if (!$role) {
                throw new Exception('Rôle non trouvé avec l\'ID: ' . $roleId);
            }

            if($roleId != 3){
                throw new Exception('Le compte de l\'utilisateur doit avoir un rôle CLIENT');
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
        }

        event(new ClientEvent($client, $user));

    }

    public function validateData($user)
    {
        // Vérifier si au moins une des clés de 'user' est renseignée
        if (!empty($user['nom']) || !empty($user['prenom']) || !empty($user['login']) || !empty($user['photo']) || !empty($user['password'])) {
            return Validator::make($user, [
                'nom' => 'required|string|min:2|max:255',
                'prenom' => 'required|string|min:2|max:255',
                'login' => 'required|string|min:4|max:255|unique:users,login',
                'photo' => 'image|mimes:jpeg,png,jpg,gif|max:40',
                'password' => ['confirmed', new CustomPasswordRule()],
                'password_confirmation' => 'required',
                'role.id' => ['required_with:id', 'integer', 'exists:roles,id'],
            ]);
        }

        // Retourner un validateur vide si aucune clé n'est renseignée
        return Validator::make([], []);
    }
}
