<x-filament::page>
    <div class="grid grid-cols-1 gap-4 mb-8 md:grid-cols-2">
        <x-filament::card>
            <div class="flex items-center space-x-4">
                <div>
                    <div class="text-2xl font-bold text-primary-600">
                        {{ $cumulativeByDistrict->sum('business_count') }}
                    </div>
                    <div class="text-gray-600">Total Usaha (Semua Kecamatan)</div>
                </div>
            </div>
        </x-filament::card>
        <x-filament::card>
            <div class="flex items-center space-x-4">
                <div>
                    <div class="text-2xl font-bold text-success-600">
                        {{ $todayByVillage->sum('today_count') }}
                    </div>
                    <div class="text-gray-600">Usaha Ditambahkan Hari Ini</div>
                </div>
            </div>
        </x-filament::card>
    </div>

    <x-filament::card class="mb-8">
        <h2 class="mb-2 text-lg font-bold">Rekapitulasi Usaha per Kecamatan & Desa/Kelurahan</h2>
        <table class="mb-4 w-full table-auto">
            <thead>
                <tr>
                    <th class="px-2 py-1">Kecamatan</th>
                    <th class="px-2 py-1">Desa/Kelurahan</th>
                    <th class="px-2 py-1">Total Usaha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cumulativeByDistrict as $district)
                    <tr class="font-bold bg-gray-50">
                        <td class="px-2 py-1">{{ $district->name }}</td>
                        <td class="px-2 py-1"></td>
                        <td class="px-2 py-1">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded bg-primary-100 text-primary-700">
                                {{ $district->business_count }}
                            </span>
                        </td>
                    </tr>
                    @foreach($cumulativeByVillage->where('district_id', $district->id) as $village)
                        <tr>
                            <td class="px-2 py-1"></td>
                            <td class="px-2 py-1">{{ $village->name }}</td>
                            <td class="px-2 py-1">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded bg-primary-50 text-primary-700">
                                    {{ $village->businesses_count }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </x-filament::card>

    <x-filament::card>
        <h2 class="mb-2 text-lg font-bold">Progres Hari Ini per Kecamatan & Desa/Kelurahan</h2>
        <table class="w-full table-auto">
            <thead>
                <tr>
                    <th class="px-2 py-1">Kecamatan</th>
                    <th class="px-2 py-1">Desa/Kelurahan</th>
                    <th class="px-2 py-1">Usaha Ditambahkan Hari Ini</th>
                </tr>
            </thead>
            <tbody>
                @foreach($todayByDistrict as $district)
                    <tr class="font-bold bg-gray-50">
                        <td class="px-2 py-1">{{ $district->name }}</td>
                        <td class="px-2 py-1"></td>
                        <td class="px-2 py-1">
                            <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded bg-success-100 text-success-700">
                                {{ $district->villages->sum('today_count') }}
                            </span>
                        </td>
                    </tr>
                    @foreach($district->villages as $village)
                        @if($village->today_count > 0)
                        <tr>
                            <td class="px-2 py-1"></td>
                            <td class="px-2 py-1">{{ $village->name }}</td>
                            <td class="px-2 py-1">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded bg-success-50 text-success-700">
                                    {{ $village->today_count }}
                                </span>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </x-filament::card>
</x-filament::page>
