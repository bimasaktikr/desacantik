<div>
    <x-filament::modal
        id="village-map-modal"
        width="4xl"
        :visible="$showMapModal"
        close-on-click-away
        close-on-escape
        wire:model="showMapModal"
    >
        <x-slot name="heading">
            Peta Desa
        </x-slot>

        <x-slot name="description">
            <div id="map" style="height: 500px; width: 100%;"></div>
        </x-slot>

        <x-slot name="footerActions">
            <x-filament::button
                wire:click="closeModal"
                color="gray"
            >
                Tutup
            </x-filament::button>
        </x-slot>
    </x-filament::modal>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            console.log('Livewire initialized');

            let map = null;
            let geojsonLayer = null;

            window.addEventListener('openMapModal', () => {
                console.log('openMapModal event received');
                if (map) {
                    map.remove();
                }
                initializeMap(@this.geojsonUrl);
            });

            function initializeMap(geojsonUrl) {
                console.log('Initializing map with URL:', geojsonUrl);

                // Wait for modal to be fully rendered
                const observer = new MutationObserver((mutations, obs) => {
                    const mapContainer = document.getElementById('map');
                    if (mapContainer) {
                        console.log('Map container found');
                        obs.disconnect();

                        // Initialize map
                        map = L.map('map').setView([-6.2088, 106.8456], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);

                        // Fetch and add GeoJSON
                        fetch(geojsonUrl)
                            .then(response => response.json())
                            .then(data => {
                                console.log('GeoJSON data loaded');
                                if (geojsonLayer) {
                                    map.removeLayer(geojsonLayer);
                                }
                                geojsonLayer = L.geoJSON(data, {
                                    style: {
                                        color: '#3388ff',
                                        weight: 2,
                                        fillOpacity: 0.2
                                    }
                                }).addTo(map);
                                map.fitBounds(geojsonLayer.getBounds());

                                // Force a resize event to ensure the map renders properly
                                setTimeout(() => {
                                    map.invalidateSize();
                                }, 100);
                            })
                            .catch(error => {
                                console.error('Error loading GeoJSON:', error);
                            });
                    }
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        });
    </script>
    @endpush
</div>
