<x-filament-panels::page>
    <div x-data="{ activeTab: @entangle('activeTab') }">

        {{-- 1. Debug Text --}}
        <div class="mb-8 text-sm text-gray-500" x-text="'Current active tab: ' + activeTab"></div>

        {{-- 2. Form --}}
        <div class="mb-8">
            {{ $this->form }}
            <x-filament::button
                type="button"
                wire:click="applyFilter"
                wire:loading.attr="disabled"
                wire:target="applyFilter"
                class="mt-4"
            >
                Apply Filter
            </x-filament::button>
        </div>

        {{-- 3. Tabs --}}
        <div class="mb-8">
            <x-filament::tabs>
                <x-filament::tabs.item
                    :active="$activeTab === 'daily'"
                    :icon="'heroicon-o-presentation-chart-line'"
                    wire:click="$set('activeTab', 'daily')"
                    class="transition-colors duration-200"
                >
                    Tren Harian
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="$activeTab === 'cumulative'"
                    :icon="'heroicon-o-presentation-chart-line'"
                    wire:click="$set('activeTab', 'cumulative')"
                    class="transition-colors duration-200"
                >
                    Tren Kumulatif
                </x-filament::tabs.item>
            </x-filament::tabs>
        </div>

        {{-- 4. Charts --}}
        <div>
            <div x-show="activeTab === 'daily'" x-transition>
                @livewire(\App\Filament\Widgets\BusinessChart::class, ['villageId' => $this->villageId, 'userId' => $this->userId], key($this->villageId . '_daily'))
            </div>
            <div x-show="activeTab === 'cumulative'" x-transition>
                @livewire(\App\Filament\Widgets\BusinessCumulativeChart::class, ['villageId' => $this->villageId, 'userId' => $this->userId], key($this->villageId . '_cumulative'))
            </div>
        </div>

    </div>
</x-filament-panels::page>
