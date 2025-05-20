<x-filament-panels::page>
    @php
        $hasMissingGeojson = \App\Models\Village::whereNull('geojson_path')->exists();
    @endphp

    @if ($hasMissingGeojson)
        <div class="text-sm font-semibold text-red-600">
            Masih ada desa/kelurahan yang belum memiliki peta digital.
        </div>
        <div class="mb-4">
            <x-filament::button
                wire:click="splitBasemap"
                icon="heroicon-o-map"
                color="success">
                Split Basemap ke Peta Desa
            </x-filament::button>
        </div>

    @endif
    {{ $this->table }}
</x-filament-panels::page>
