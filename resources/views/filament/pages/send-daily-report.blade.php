<x-filament::page>
    <div class="flex justify-center pt-16 min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
        <div class="p-10 w-full max-w-lg rounded-2xl border border-gray-100 shadow-2xl backdrop-blur-md bg-white/80">
            <div class="flex flex-col items-center mb-8">
                <div class="p-4 mb-4 rounded-full shadow bg-primary-100">
                    <x-heroicon-o-paper-airplane class="w-10 h-10 text-primary-600"/>
                </div>
                <h2 class="mb-1 text-3xl font-extrabold text-gray-900">Kirim Laporan Harian</h2>
                <p class="mb-2 text-lg text-gray-500">Pilih penerima dan kirim laporan harian secara instan</p>
            </div>
            <form wire:submit.prevent="sendReport" class="space-y-6">
                {{ $this->form }}
                <x-filament::button type="submit" color="primary" class="w-full h-12 text-lg rounded-xl shadow-md transition-transform hover:scale-105">
                    <x-heroicon-o-paper-airplane class="mr-2 -ml-1 w-5 h-5" />
                    Kirim Laporan Sekarang
                </x-filament::button>
            </form>
        </div>
    </div>
</x-filament::page>
