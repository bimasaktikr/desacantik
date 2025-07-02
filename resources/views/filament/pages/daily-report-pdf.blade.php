<!DOCTYPE html>
<html>
<head>
    <title>Laporan Harian</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        h2 { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 4px 8px; text-align: left; }
        th { background: #eee; }
        .district-row { font-weight: bold; background: #f3f4f6; }
        .stat-cards { display: flex; gap: 24px; margin-bottom: 32px; }
        .stat-card { flex: 1; border: 1px solid #ddd; border-radius: 8px; padding: 16px; background: #f9fafb; text-align: center; }
        .stat-value { font-size: 2em; font-weight: bold; color: #2563eb; }
        .stat-label { color: #555; margin-top: 4px; }
        .stat-success { color: #16a34a; }
    </style>
</head>
<body>
    <h1>Laporan Harian</h1>
    <p>Tanggal: {{ now()->format('d M Y') }}</p>

    <div class="stat-cards">
        <div class="stat-card">
            <div class="stat-value">{{ $cumulativeByDistrict->sum('business_count') }}</div>
            <div class="stat-label">Total Usaha (Semua Kecamatan)</div>
        </div>
        <div class="stat-card">
            <div class="stat-value stat-success">{{ $todayByVillage->sum('today_count') }}</div>
            <div class="stat-label">Usaha Ditambahkan Hari Ini</div>
        </div>
    </div>

    <h2>Rekapitulasi Usaha per Kecamatan & Desa/Kelurahan</h2>
    <table>
        <thead>
            <tr>
                <th>Kecamatan</th>
                <th>Desa/Kelurahan</th>
                <th>Total Usaha</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cumulativeByDistrict as $district)
                <tr class="district-row">
                    <td>{{ $district->name }}</td>
                    <td></td>
                    <td>{{ $district->business_count }}</td>
                </tr>
                @foreach($cumulativeByVillage->where('district_id', $district->id) as $village)
                    <tr>
                        <td></td>
                        <td>{{ $village->name }}</td>
                        <td>{{ $village->businesses_count }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <h2>Progres Hari Ini per Kecamatan & Desa/Kelurahan</h2>
    <table>
        <thead>
            <tr>
                <th>Kecamatan</th>
                <th>Desa/Kelurahan</th>
                <th>Usaha Ditambahkan Hari Ini</th>
            </tr>
        </thead>
        <tbody>
            @foreach($todayByDistrict as $district)
                <tr class="district-row">
                    <td>{{ $district->name }}</td>
                    <td></td>
                    <td>{{ $district->villages->sum('today_count') }}</td>
                </tr>
                @foreach($district->villages as $village)
                    @if($village->today_count > 0)
                    <tr>
                        <td></td>
                        <td>{{ $village->name }}</td>
                        <td>{{ $village->today_count }}</td>
                    </tr>
                    @endif
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>