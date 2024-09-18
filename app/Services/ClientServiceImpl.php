<?php

namespace App\Services;
use App\Facades\ClientRepositoryFacade as clientRepository;
use App\Models\Role;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Writer\PngWriter;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Facades\UploadServiceFacade as UploadService;
use App\Facades\UploadServiceImgurFacade as UploadServiceImgur;
use App\Mail\SendMailAttachment;
use App\Models\Client;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

class ClientServiceImpl implements ClientServiceInterface
{

    /*protected $imgur; 
    public function __construct(ImgurService $imgur){
        $this->imgur = $imgur;
    }*/

    public function all()
    {
        return clientRepository::all();
    }

    public function create(array $data){
        return DB::transaction(function () use ($data) {  
            $idCategoryClient = $data['category_client']['id'];
            $maxMontant = null;
            if($idCategoryClient == 2){
                $maxMontant = $data['max_montant'];
                if(!$maxMontant){
                    throw new Exception('Le montant maximum doit être renseigné pour les clients de catégorie "Silver(Argent)".');
                }
            }    
            return clientRepository::create([
                'surname' => $data['surname'],
                'telephone' => $data['telephone'],
                'adresse' => $data['adresse'],
                'category_client_id' => $idCategoryClient,
                'max_montant' => $maxMontant ?? null,
            ]);
        });
    }

    /*public function create(array $data){
        try {
            DB::beginTransaction();

            $client = clientRepository::create($data);
            
            if (isset($data['user'])) {
                $this->associateUserToClient($data['user'], $client);
            }
    
            DB::commit();
            return $client;
        } catch(Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création du client ou de l\'utilisateur: ' . $e->getMessage(), ['exception' => $e]);
            throw new Exception('Erreur lors de la création du client ou de l\'utilisateur: ' . $e->getMessage());
        }
    }*/
    public function associateUserToClient(array $user, Client $client){
        if (!$client) {
            throw new Exception("Client not found");
        }
    
        $roleId = $user['role']['id'] ?? null;
        if (!$roleId) {
            throw new Exception('Le rôle de l\'utilisateur est manquant.');
        }
    
        $role = Role::find($roleId);
        if (!$role) {
            throw new Exception('Rôle non trouvé avec l\'ID: ' . $roleId);
        }
    
        $photoUrl = 'https://cdn-icons-png.flaticon.com/128/17346/17346780.png';
        if (isset($user['photo']) && $user['photo'] instanceof UploadedFile) {
            $photoUrl = $user['photo']->getRealPath();
        }
    
        Log::info('Création de l\'utilisateur avec les données suivantes: ', $user); // Ajout de log
    
        $savedUser = User::create([
            'nom' => $user['nom'],
            'prenom' => $user['prenom'],
            'login' => $user['login'],
            'password' => $user['password'],
            'photo' => $photoUrl,
            'role_id' => $role->id
        ]);
    
        if (!$savedUser) {
            throw new Exception('Erreur lors de la création de l\'utilisateur.');
        }
        $client->user()->associate($savedUser);
        $client->save();

    }
    /*public function createBeforeV2(array $data)
    {
        try{
            DB::beginTransaction();
            $client = clientRepository::create($data);
            $savedUser = null;
            if(isset($data['user'])){
                $user = $data['user'];
                $roleId = $user['role']['id'] ?? null;
                if (!$roleId) {
                    throw new Exception('Le rôle de l\'utilisateur est manquant.');
                }
    
                $role = Role::find($roleId);
                if (!$role) {
                    throw new Exception('Rôle non trouvé avec l\'ID: ' . $roleId);
                }

                // Gestion de l'image
                $photoUrl = 'https://cdn-icons-png.flaticon.com/128/17346/17346780.png'; // Par défaut

                if(isset($user['photo']) && $user['photo'] instanceof UploadedFile){
                    $photo = $user['photo'];
                    try{
                        $fullImagePath = $photo->getRealPath();
                        $imgurUrl = UploadServiceImgur::uploadImageWithImgur($fullImagePath);
                        $photoUrl = $imgurUrl ?: UploadService::uploadImage($photo); // Sauvegarde locale si Imgur échoue
                    }catch(Exception $e){
                        throw new Exception('Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                        //$photoUrl = $uploadService->uploadImage($photo);
                    }
                }

                $savedUser = User::create([
                    'nom' => $user['nom'],
                    'prenom' => $user['prenom'],
                    'login' => $user['login'],
                    'password' => $user['password'],
                    'photo' => $photoUrl,
                    'role_id' => $role->id
                ]);
                $client->user()->associate($savedUser);
                $client->save();
            }

            //info stocker dans le qrcode 
            $qrCodeContent = 'surnom: ' . $client->surname . ' | telephone: ' . $client->telephone;
             $qrCode = Builder::create()
             ->writer(new PngWriter())
             ->data($qrCodeContent)
             ->encoding(new Encoding('UTF-8'))
             ->size(300)
             ->build();

             /*$qrcodePath = storage_path('app/temp/qrcode_' . uniqid() . '.png');
             $qrCode->saveToFile($qrcodePath);*/

           
            // Chemin pour enregistrer le QR code temporaire
            //$tempDir = storage_path('app/temp'); // Utilisation de storage_path() pour obtenir le chemin absolu
            //$qrTempPath = $tempDir . '/qrcode_' . uniqid() . '.png';

            // Vérifiez si le répertoire existe, sinon créez-le
            /*if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true); // Créez le répertoire s'il n'existe pas
            }*/

            // Enregistrer le QR code temporaire
            //file_put_contents($qrTempPath, $qrCode->getString()); // Utilisation de file_put_contents() pour écrire le fichier

            // Télécharger l'image sur Imgur

             /*$imgurUrl = UploadServiceImgur::uploadImageWithImgur($qrTempPath);
             $client->qrcode = $imgurUrl;
             $client->save();*/
             // Générer la carte de fidélité en PDF 
            /*$pdf = Pdf::loadView('fidelity_card', [
                'pseudo' => $savedUser->nom . ' ' . $savedUser->prenom,
                'email' => $savedUser->login,
                'qrCodeImageUrl' => $qrTempPath,
            ]);*/

            // Définir le chemin temporaire du PDF
            /*$tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true); // Créez le répertoire s'il n'existe pas
            }*/

            // Sauvegarde temporaire du PDF
            /*$pdfPath = $tempDir . '/fidelite_card_' . uniqid() . '.pdf';
            $pdf->save($pdfPath);


            Mail::to($savedUser->login)->send(new SendMailAttachment($client, $savedUser, $pdfPath));
            
            DB::commit();
            return $client;
        }catch(Exception $e){
            DB::rollBack();
            Log::error('Erreur lors de la création du client ou de l\'utilisateur: ' . $e->getMessage(), ['exception' => $e]);
            throw new Exception('Erreur lors de la création du client ou de l\'utilisateur: ' . $e->getMessage());
        }
    }*/

    public function update(array $data, $id)
    {
        // TODO: Implement update() method.
        return clientRepository::where('id', $id)->update($data);
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
        return clientRepository::destroy($id);
    }

    public function getByPhone($phone)
    {
        // TODO: Implement getByPhone() method.
        return clientRepository::where('telephone', $phone)->first();
    }

    public function find($id)
    {
        if (!is_numeric($id)) {
            throw new Exception('L\'identifiant doit être un nombre valide.');
        }

        return clientRepository::find($id);
    }

    public function clientWithHisAccount($id)
    {
        try{
            if (!is_numeric($id)) {
                throw new Exception('L\'identifiant doit être un nombre valide.');
            }
            return clientRepository::clientWithHisAccount($id);
        }catch(Exception $e){
            throw new Exception('Erreur lors de la récupération du client avec son compte ' . $e->getMessage());
        }
    }

    public function getClientWithHisDebts($id){
        try{
            return clientRepository::getClientWithHisDebts($id);
        }catch(Exception $e){
            throw new Exception('Erreur lors de la récupération du client avec ses dettes ' . $e->getMessage());
        }
    }
}