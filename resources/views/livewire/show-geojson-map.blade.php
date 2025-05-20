<div>
    <x-filament::modal
        id="mapModal"
        :visible="$showMapModal"
        close-on-click-away
        wire:model="showMapModal">
        <x-slot name="header">
            <h2 class="text-lg font-bold">Peta SLS</h2>
        </x-slot>

        <div id="map" class="w-full h-96"></div>

        <x-slot name="footer">
            <x-filament::button wire:click="$set('showMapModal', false)">Tutup</x-filament::button>
        </x-slot>
    </x-filament::modal>

    <script>
        if (!window.hasInitializedMapListener) {
            window.addEventListener('openMapModal', event => {
                setTimeout(() => {
                    const checkAndInitMap = () => {
                        const mapContainer = document.getElementById('map');
                        console.log('Inside set Timeout');
                        if (!mapContainer || mapContainer.offsetHeight === 0) {
                            // Retry in 100ms until modal is fully rendered
                            return setTimeout(checkAndInitMap, 100);
                        }
                        // Check if Leaflet is already loaded

                        // Clean old map
                        if (window.map) {
                            window.map.remove();
                        }

                        window.map = L.map('map').setView([-7.984, 112.638], 17);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(window.map);

                        fetch(event.detail.url)
                            .then(res => res.json())
                            .then(data => {
                                const layer = L.geoJSON(data, {
                                    style: {
                                        color: 'blue',
                                        weight: 2,
                                        fillOpacity: 0.4
                                    },
                                    onEachFeature: function (feature, layer) {
                                        if (feature.properties?.nmsls) {
                                            layer.bindPopup(`<strong>${feature.properties.nmsls}</strong>`);
                                        }
                                    }
                                }).addTo(window.map);

                                window.map.fitBounds(layer.getBounds());
                            });
                    };

                    checkAndInitMap();
                }, 300);

            });

            window.hasInitializedMapListener = true;
        }
    </script>
</div>
