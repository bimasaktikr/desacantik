<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ShowGeojsonMap extends Component
{
    public $showMapModal = false;
    public $geojsonUrl;

    protected $listeners = ['showMap'];

    public function showMap($slsId)
    {
        $sls = \App\Models\Sls::findOrFail($slsId);
        Log::info('SLS ID: ' . $slsId);
        if ($sls->geojson_path && Storage::disk('public')->exists($sls->geojson_path)) {
            $this->geojsonUrl = Storage::disk('public')->url($sls->geojson_path);
            $this->showMapModal = true;
            Log::info('GeoJSON URL: ' . $this->geojsonUrl);
            $this->dispatch('openMapModal', [
                'url' => $this->geojsonUrl,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.show-geojson-map');
    }
}
