<x-filament-widgets::widget>
    {{-- <x-filament::section>
        {{-- {{ $this->form }}
    </x-filament::section> --}}

    <div class="mt-4">
        <canvas x-data="{
            chart: null,
            init() {
                const ctx = this.$el.getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {{ json_encode($this->getData()) }},
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        }" class="h-80"></canvas>
    </div>
</x-filament-widgets::widget>
