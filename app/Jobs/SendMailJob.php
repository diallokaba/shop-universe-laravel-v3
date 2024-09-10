<?php

namespace App\Jobs;

use App\Facades\UploadServiceImgurFacade as UploadWithImgur;
use App\Mail\SendMailAttachment;
use App\Models\Client;
use App\Models\User;
use App\utils\DocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Facades\UploadServiceImgurFacade as UploadServiceImgur;

class SendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $user;
    public $client;
    /**
     * Create a new job instance.
     */
    public function __construct($user, Client $client)
    {
        $this->user = $user;
        $this->client = $client;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        
        
        /*$qrcodeInfo = 'email: ' . $this->user->email;
       
        $qrTempPath = DocumentService::generateQrcode($qrcodeInfo, 'qrcode_' . uniqid() . '_' . time() . '.png');
        $imgurUrl = UploadWithImgur::uploadImageWithImgur($qrTempPath);*/
        //$this->user->client->qrcode = $imgurUrl;
        //$this->user->client->save();

        /*$pdfPath = DocumentService::generatePdf('fidelity_card', [
            'pseudo' => $this->user->nom . ' ' .  $this->user->prenom,
            'email' =>  $this->user->login,
            'qrCodeImageUrl' => $qrTempPath,
        ], 'fidelite_card_' . uniqid() . '_' . time() . '.pdf');

        Mail::to( $this->user->login)->send(new SendMailAttachment($this->user, $pdfPath));*/


        if(isset($this->user)){
            $qrCodeContent = 'telephone: ' . $this->client->telephone;
            $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->data($qrCodeContent)
            ->encoding(new Encoding('UTF-8'))
            ->size(300)
            ->build();

            $qrcodePath = storage_path('app/temp/qrcode_' . uniqid() . '.png');
            $qrCode->saveToFile($qrcodePath);

            
            // Chemin pour enregistrer le QR code temporaire
            $tempDir = storage_path('app/temp'); // Utilisation de storage_path() pour obtenir le chemin absolu
            $qrTempPath = $tempDir . '/qrcode_' . uniqid() . '.png';

            // Vérifiez si le répertoire existe, sinon créez-le
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true); // Créez le répertoire s'il n'existe pas
            }

            // Enregistrer le QR code temporaire
            file_put_contents($qrTempPath, $qrCode->getString()); // Utilisation de file_put_contents() pour écrire le fichier

            // Télécharger l'image sur Imgur

            $imgurUrl = UploadServiceImgur::uploadImageWithImgur($qrTempPath);
            $this->client->qrcode = $imgurUrl;
            $this->client->save();
            // Générer la carte de fidélité en PDF 
            $pdf = Pdf::loadView('fidelity_card', [
                'pseudo' => $this->user->nom . ' ' .  $this->user->prenom,
                'email' => $this->user->login,
                'qrCodeImageUrl' => $qrTempPath,
            ]);

            // Définir le chemin temporaire du PDF
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true); // Créez le répertoire s'il n'existe pas
            }

            // Sauvegarde temporaire du PDF
            $pdfPath = $tempDir . '/fidelite_card_' . uniqid() . '.pdf';
            $pdf->save($pdfPath);


            Mail::to($this->user->login)->send(new SendMailAttachment($this->client, $this->user, $pdfPath));
        }
        

    }
}
