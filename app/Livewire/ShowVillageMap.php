<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Village;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;

class ShowVillageMap extends Component
{
    public $showMapModal = false;
    public $geojsonUrl = null;
    public $villageId = null;

    public function mount()
    {
        Log::info('ShowVillageMap component mounted');
    }

    #[On('showMap')]
    public function showMap($villageId)
    {
        Log::info('ShowVillageMap received showMap event for village: ' . $villageId);

        $village = Village::find($villageId);
        if (!$village) {
            Log::error('Village not found: ' . $villageId);
            return;
        }

        if (!$village->geojson_path) {
            Log::error('No GeoJSON file found for village: ' . $villageId);
            return;
        }

        $this->villageId = $villageId;
        $this->geojsonUrl = asset('storage/' . $village->geojson_path);
        Log::info('Setting GeoJSON URL: ' . $this->geojsonUrl);

        $this->showMapModal = true;
        Log::info('Opening map modal');

        $this->dispatch('openMapModal');
    }

    public function closeModal()
    {
        $this->showMapModal = false;
    }

    public function render()
    {
        return view('livewire.show-village-map');
    }
}
