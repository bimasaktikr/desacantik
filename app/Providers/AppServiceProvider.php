<?php

namespace App\Providers;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Livewire\ShowVillageMap;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        parent::register();
        FilamentView::registerRenderHook('panels::body.end', fn(): string => Blade::render("@vite('resources/js/app.js')"));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Gate::define('viewApiDocs', function (User $user) {
            return true;
        });
        // Gate::policy()
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('discord', \SocialiteProviders\Google\Provider::class);
        });

        FilamentAsset::register([
            Css::make('leaflet-css' , 'https://unpkg.com/leaflet@1.9.3/dist/leaflet.css'),
            Js::make('leaflet-js' , 'https://unpkg.com/leaflet@1.9.3/dist/leaflet.js'),
        ]);

        Livewire::component('show-village-map', ShowVillageMap::class);
    }
}
