<?php

namespace App\Http\Controllers;

use App\Notifications\SendNotificationForSettledDebt;
use Illuminate\Http\Request;

use App\Facades\ClientServiceFacade as clientService;
use App\Models\Client;
use App\Models\Dette;
use App\Services\SendSMSWithInfoBip;
use Exception;

class NotificationClientController extends Controller
{
    private $sendSmsWithInfoBip;
    public function __construct(){
        $this->sendSmsWithInfoBip = new SendSMSWithInfoBip();
    } 
   public function sendRemainderNotificationForSettledDebt($id){
        try{    
            $id = (int)$id;
            $client = clientService::find($id);

            if(!$client){
                return response()->json(['message' => 'Client not found'], 404);
            }

            $debts = Dette::with(['paiements'])->where('client_id', $client->id)->where('statut', 'nonsolde')->get();
            
            $montantRestant = 0;

            foreach($debts as $debt){
                $montantPaye = $debt->paiements->sum('montant');
                $montantRestant += $debt->montant - $montantPaye;
            }

            $message = "Bonjour " . $client->nom . " " . $client->prenom . ", votre total de dette est de : " . number_format($montantRestant, 0, ',', ' ') . " FCFA.";
            $this->sendSmsWithInfoBip->sendSms($message, $client->telephone);
            $client->notify(new SendNotificationForSettledDebt($montantRestant, $client->nom, $client->prenom));
            return response()->json(['message' => 'Opération réussie avec succès'], 200);
        }catch(Exception $e){
            return response()->json(['error' => 'Erreur lors de la notification du client : ' . $e->getMessage()], 500);
        }
   }

   public function sendRemainderNotificationForManyClient(Request $request){
    if(!$request->has('clients')){
        return response()->json(['message' => 'Veuillez fournir la clé clients'], 400);
    }
    $clients = $request->get('clients');
    if(empty($clients)){
        return response()->json(['message' => 'Aucun client fourni'], 400);
    }
    $nbClientSendNotif = 0;
    foreach($clients as $client){
        $telephone = $client['telephone'];

        $client = Client::where('telephone', $telephone)->first();            

        if($client){
            $debts = Dette::with(['paiements'])->where('client_id', $client->id)->where('statut', 'nonsolde')->get();
            if(!empty($debts)){
                $nbClientSendNotif++;
                $montantRestant = 0;

                foreach($debts as $debt){
                    $montantPaye = $debt->paiements->sum('montant');
                    $montantRestant += $debt->montant - $montantPaye;
                }

                $message = "Bonjour " . $client->nom . " " . $client->prenom . ", votre total de dette est de : " . number_format($montantRestant, 0, ',', ' ') . " FCFA.";
                $this->sendSmsWithInfoBip->sendSms($message, $client->telephone);
                $client->notify(new SendNotificationForSettledDebt($montantRestant, $client->nom, $client->prenom));
            }
        }

        if($nbClientSendNotif > 0){
            return response()->json(['message' => $nbClientSendNotif . ' clients ont été notifiés'], 200);
        }else{
            return response()->json(['message' => 'Aucun client n\'a été notifié'], 404);
        }
    }
   }

   public function sendRemainderSMSNotification(Request $request){
        if(!$request->has('clients')){
            return response()->json(['message' => 'Veuillez fournir la clé clients'], 400);
        }else if(!$request->has('message')){
            return response()->json(['message' => 'Veuillez fournir la clé message'], 400);
        }
        $clients = $request->get('clients');
        $message = $request->get('message');
        if(empty($clients) || empty($message)){
            return response()->json(['message' => 'Le client ou le message ne doivent pas être vide'], 400);
        }
        $nbClientSendNotif = 0;

        foreach($clients as $client){
            $telephone = $client['telephone'];

            $client = Client::where('telephone', $telephone)->first();            

            if($client){
                //$debts = Dette::with(['paiements'])->where('client_id', $client->id)->where('statut', 'nonsolde')->get();
                $debts = Dette::where('client_id', $client->id)->where('statut', 'nonsolde')->get();
                if(!empty($debts)){
                    /*
                    $montantRestant = 0;

                    foreach($debts as $debt){
                        $montantPaye = $debt->paiements->sum('montant');
                        $montantRestant += $debt->montant - $montantPaye;
                    }*/

                    $nbClientSendNotif++;
                    $this->sendSmsWithInfoBip->sendSms($message, $client->telephone);
                    $client->notify(new SendNotificationForSettledDebt(null, $client->nom, $client->prenom, $message));
                }
            }
        }

        if($nbClientSendNotif > 0){
            return response()->json(['message' => $nbClientSendNotif . ' clients ont été notifiés'], 200);
        }else{
            return response()->json(['message' => 'Aucun client n\'a été notifié'], 404);
        }
   }

   
    public function notifications(Request $request){
        $user = auth()->user();
        $client = Client::where('user_id', $user->id)->first();

        if (!$client) {
            return response()->json(['message' => 'Ce client n\'a pas de compte utilisateur'], 404);
        }

        $query = $client->notifications();

        if ($request->has('unread') && $request->get('unread') == 'true') {
            $query->whereNull('read_at');
        } elseif ($request->has('unread') && $request->get('unread') == 'false') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate(10);
        return response()->json($notifications);
    }

}
