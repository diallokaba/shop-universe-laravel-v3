<?php

namespace App\Services;

use App\Mail\WelcomeEmail;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserServiceImpl implements UserServiceInterface
{

    private $userRepository;
    public function __construct(UserRepositoryInterface $userRepository){
        $this->userRepository = $userRepository;
    }

    public function create(array $data){
        
        $roleId = $data['role']['id'] ?? null;
        
        $role = Role::find($roleId);
        if($role->nomRole != 'ADMIN' && $role->nomRole != 'BOUTIQUIER'){
            throw new Exception('Le role de l\'utilisateur doit être ADMIN ou BOUTIQUIER');
        }

        $data['password'] = Hash::make($data['password']);
        $data['role_id'] = $role->id;

        //verify with preg_match if the login is a valid email address
        if (filter_var($data['login'], FILTER_VALIDATE_EMAIL)) {
            Mail::to($data['login'])->send(new WelcomeEmail(["nom" => $data['nom'], "prenom" => $data['prenom']]));
        }
        $user = $this->userRepository->create($data);
        return $user;
    }
    public function all(){
        $this->userRepository->all();
    }
    
    public function delete($id){

    }
    public function query($params){
        try{
            $users = $this->userRepository->query();
            if($params && $params->input('role')){
                $value =  strtolower($params->input('role'));
                switch (strtoupper($value)) {
                    case 'ADMIN':
                        $users->where('role_id', 1);
                        break;
                    case 'BOUTIQUIER':
                        $users->where('role_id', 2);
                        break;
                    case 'CLIENT':
                        $users->where('role_id', 3);
                        break;
                    default:
                        break;
                }
            }

            if($params->has('active')){
                $active = strtolower($params->get('active'));
                if($active === 'oui'){
                    $users->where('active', 'OUI');
                }else if($active === 'non'){
                    $users->where('active', 'NON');
                }
            }

            $users = $users->get();

            return $users;
        }catch(Exception $e){
            throw new Exception('Erreur lors de la récupération des utilisateurs : ' . $e->getMessage());
        }
    }
    public function find($id){

    }
    public function getUserProfile($id){

    }
    public function getUsers(){

    }
    public function update($request){

    }
}