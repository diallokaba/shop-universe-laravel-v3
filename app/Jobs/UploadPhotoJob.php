<?php

namespace App\Jobs;

use App\Models\Role;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Facades\UploadServiceImgurFacade as UploadWithImgur;
use App\Facades\UploadServiceFacade as UploadWithLocal;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UploadPhotoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
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
        try{

            if(isset($this->user)){
                try{
                    $fullImagePath = $this->user->photo;
                    $imgurUrl = UploadWithImgur::uploadImageWithImgur($fullImagePath);
                    $photoUrl = $imgurUrl ?: UploadWithLocal::uploadImage($fullImagePath);   

                    $this->user->photo = $photoUrl;
                    $this->user->save();
                }catch(Exception $e){
                    throw new Exception('Erreur lors de l\'upload de l\'image : ' . $e->getMessage());
                }
            }

        }catch(Exception $e){
            Log::error('Erreur dans le job UploadPhotoJob: ' . $e->getMessage(), ['exception' => $e]);
            throw new Exception('Erreur dans le job UploadPhotoJob: ' . $e->getMessage());
        }
    }
}
