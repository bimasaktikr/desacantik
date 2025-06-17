@php
    use Illuminate\Support\Facades\Auth;
@endphp

<x-filament-panels::page>
    <div x-data="{ activeTab: @entangle('activeTab') }">
        <div class="mb-8">
            <x-filament::tabs>
                <x-filament::tabs.item
                    :active="$activeTab === 'summary'"
                    wire:click="$set('activeTab', 'summary')"
                    class="transition-colors duration-200"
                >
                    Summary Table
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    :active="$activeTab === 'chart'"
                    wire:click="$set('activeTab', 'chart')"
                    class="transition-colors duration-200"
                >
                    Progress Chart
                </x-filament::tabs.item>
            </x-filament::tabs>
        </div>

        <div x-show="activeTab === 'summary'" x-transition>
            {{ $this->table }}
        </div>

        <div x-show="activeTab === 'chart'" x-transition>
            @if (Auth::user()->roles->contains('name', 'Employee'))
                <div class="mb-8">
                    <x-filament::tabs>
                        <x-filament::tabs.item
                            :active="$mode === 'uploads'"
                            wire:click="$set('mode', 'uploads')"
                            class="transition-colors duration-200"
                        >
                            Uploads
                        </x-filament::tabs.item>

                        <x-filament::tabs.item
                            :active="$mode === 'businesses'"
                            wire:click="$set('mode', 'businesses')"
                            class="transition-colors duration-200"
                        >
                            Data Usaha
                        </x-filament::tabs.item>
                    </x-filament::tabs>
                </div>

                <div x-show="$mode === 'uploads'" x-transition>
                    @livewire(\App\Filament\Widgets\MahasiswaProgressLineChart::class, ['mode' => 'uploads'])
                </div>

                <div x-show="$mode === 'businesses'" x-transition>
                    @livewire(\App\Filament\Widgets\MahasiswaProgressLineChart::class, ['mode' => 'businesses'])
                </div>
            @else
                {{ $this->table }}
            @endif
        </div>
    </div>
</x-filament-panels::page>
