<x-filament-panels::page>
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush

    @php
        $hasMissingGeojson = \App\Models\Village::whereNull('geojson_path')->exists();
        $villagesWithoutMap = \App\Models\Village::whereNull('geojson_path')->count();
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

    @if($villagesWithoutMap > 0)
        <div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300" role="alert">
            <div class="flex items-center">
                <svg class="flex-shrink-0 w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM10 15a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm1-4a1 1 0 0 1-2 0V6a1 1 0 0 1 2 0v5Z"/>
                </svg>
                <span class="font-medium">{{ $villagesWithoutMap }} desa belum memiliki peta digital.</span>
                <x-filament::button
                    wire:click="splitBasemap"
                    color="warning"
                    class="ml-4"
                >
                    Split Basemap
                </x-filament::button>
            </div>
        </div>
    @endif

    {{ $this->table }}

    <livewire:show-village-map />
</x-filament-panels::page>
