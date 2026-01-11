<?php

namespace App\Providers;

use App\Services\Import\DatabaseService;
use App\Services\Import\ImportService;
use Illuminate\Support\ServiceProvider;

class ImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatabaseService::class, function ($app) {
            return new DatabaseService();
        });
        
        $this->app->singleton(ImportService::class, function ($app) {
            return new ImportService($app->make(DatabaseService::class));
        });
    }

    public function boot(): void
    {
        //
    }
}