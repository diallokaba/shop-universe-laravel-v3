<?php

namespace App\Providers;

use App\Facades\UploadServiceFacade;
use App\Repositories\ArticleRepositoryImpl;
use App\Repositories\ArticleRepositoryInterface;
use App\Repositories\ClientRepositoryImpl;
use App\Services\ArticleServiceImpl;
use App\Services\ArticleServiceInterface;
use App\Services\ClientServiceImpl;
use App\Services\DetteServiceImpl;
use App\Services\DetteServiceInterface;
use App\Services\ImgurService;
use App\Services\SendSMSWithInfoBip;
use App\Services\UploadService;
use App\Services\UploadServiceImgur;

use Illuminate\Support\ServiceProvider;
use App\Observers\UserObserver;
use App\Observers\ClientObserver;
use App\Models\User;
use App\Models\Client;
use App\Repositories\DetteRepositoryImpl;
use App\Repositories\DetteRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ArticleRepositoryInterface::class, ArticleRepositoryImpl::class);
        $this->app->singleton(ArticleServiceInterface::class, ArticleServiceImpl::class);
        $this->app->singleton(DetteRepositoryInterface::class, DetteRepositoryImpl::class);
        $this->app->singleton(DetteServiceInterface::class, DetteServiceImpl::class);
        $this->app->singleton('clientRepository', function () {
            return new ClientRepositoryImpl();
        });
        $this->app->singleton('clientService', function () {
            return new ClientServiceImpl();
        });

        $this->app->singleton('uploadService', function () {
            return new UploadService();
        });

        $this->app->singleton('uploadServiceImgur', function () {
            return new UploadServiceImgur();
        });

        /*$this->app->singleton('SendSMSWithInfoBip', function () {
            return new SendSMSWithInfoBip();
        });*/

        /*$this->app->singleton('imgur', function(){
            return new ImgurService(new Client());
        });*/

        // Vous pouvez aussi lier par interface
        //$this->app->singleton(ClientRepositoryInterface::class, ClientRepositoryImpl::class);
        //$this->app->singleton(ClientServiceInterface::class, ClientServiceImpl::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Client::observe(ClientObserver::class);

    }
}
