@php
    use Illuminate\Support\Facades\Auth;
@endphp

<x-filament-panels::page>
    <div x-data="{ mode: @entangle('mode') }">
        <div class="mb-8">
            <x-filament::tabs>
                <x-filament::tabs.item
                    :active="$mode === 'table'"
                    wire:click="$set('mode', 'table')"
                    class="transition-colors duration-200"
                >
                    Tabel Upload
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="$mode === 'uploads'"
                    wire:click="$set('mode', 'uploads')"
                    class="transition-colors duration-200"
                >
                    Upload Chart
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="$mode === 'businesses'"
                    wire:click="$set('mode', 'businesses')"
                    class="transition-colors duration-200"
                >
                    Business Chart
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="$mode === 'cumulative'"
                    wire:click="$set('mode', 'cumulative')"
                    class="transition-colors duration-200"
                >
                    Cumulative Business Chart
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="$mode === 'village-summary'"
                    wire:click="$set('mode', 'village-summary')"
                    class="transition-colors duration-200"
                >
                    Village Business Summary
                </x-filament::tabs.item>
            </x-filament::tabs>
        </div>

        <div x-show="mode === 'table'" x-transition>
            {{ $this->table }}
        </div>

        <div x-show="mode === 'uploads'" x-transition>
            @livewire(\App\Filament\Widgets\MahasiswaProgressLineChart::class, [
                'mode' => 'uploads',
                'districtId' => $districtId,
                'villageId' => $villageId
            ])
        </div>

        <div x-show="mode === 'businesses'" x-transition>
            @livewire(\App\Filament\Widgets\MahasiswaProgressLineChart::class, [
                'mode' => 'businesses',
                'districtId' => $districtId,
                'villageId' => $villageId
            ])
        </div>

        <div x-show="mode === 'cumulative'" x-transition>
            @livewire(\App\Filament\Widgets\MahasiswaCumulativeChart::class, [
                'districtId' => $districtId,
                'villageId' => $villageId
            ])
        </div>

        <div x-show="mode === 'village-summary'" x-transition>
            @livewire(\App\Filament\Widgets\VillageBusinessSummary::class, [
                'districtId' => $districtId,
                'villageId' => $villageId
            ])
        </div>
    </div>
</x-filament-panels::page>
