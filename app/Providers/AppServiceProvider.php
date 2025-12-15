<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar el GeminiService
        $this->app->bind(\App\Services\GeminiService::class, function ($app) {
            return new \App\Services\GeminiService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar el ViewComposer para el sidebar
        view()->composer('layouts.sidebar', \App\View\Composers\SidebarComposer::class);
    }
}
