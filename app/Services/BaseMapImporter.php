<?php

namespace App\Services;

use App\Models\Basemap;
use App\Models\District;
use App\Models\Regency;
use App\Models\Sls;
use App\Models\Village;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class BaseMapImporter
{
    public static function importsls ($geojsonPath, $baseMapId = null)
    {
        $geojsonData = json_decode(Storage::disk('public')->get($geojsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to decode GeoJSON: ' . json_last_error_msg());
            return;
        }

        foreach ($geojsonData['features'] as $feature) {
            $props = $feature['properties'];

            // 1. Fix codes
            $regencyCode = '35' . $props['kdkab'];        // e.g. '35' prefix + kab code
            $districtCode = $regencyCode . str_pad($props['kdkec'], 3, '0', STR_PAD_LEFT);
            $villageCode = $districtCode . str_pad($props['kddesa'], 3, '0', STR_PAD_LEFT);
            $slsCode = $props['idsls'];

            // 2. Find or create Regency
            $regency = Regency::firstOrCreate(
                ['code' => $props['kdkab']],
                [
                    'name' => $props['nmkab'],
                    'slug' => Str::slug($props['nmkab'])
                ]
            );

            // 3. Find or create District (with composite code)
            $district = District::firstOrCreate(
                ['code' => $districtCode],
                [
                    'name' => $props['nmkec'],
                    'regency_id' => $regency->id,
                    'district_code' => $props['kdkec'],
                    'slug' => Str::slug($props['nmkec']),
                ]
            );

            // 4. Find or create Village
            $village = Village::firstOrCreate(
                ['code' => $villageCode],
                [
                    'name' => $props['nmdesa'],
                    'district_id' => $district->id,
                    'village_code' => $props['kddesa'],
                    'slug' => Str::slug($props['nmdesa']),
                ]
            );

            // 5. Prepare geojson for SLS feature (one per SLS)
            $slsGeojson = [
                'type' => 'FeatureCollection',
                'features' => [[
                    'type' => 'Feature',
                    'geometry' => $feature['geometry'],
                    'properties' => $props,
                ]],
            ];

            // 6. Save geojson file for SLS
            $slsFilename = "sls/{$slsCode}.geojson";
            // Storage::put($slsFilename, json_encode($slsGeojson));
            Storage::disk('public')->put("sls/{$slsCode}.geojson", json_encode($slsGeojson));


            // 7. Slug for SLS
            $slsName = $props['nmsls'] ?? "SLS {$props['idsls']}";
            $slsSlug = Str::slug($slsName . '-' . $slsCode); // Example: rt-001-rw-001-35730400010006

            // 8. Save SLS with relations and geojson path
            $sls = Sls::firstOrCreate(
                ['code' => $slsCode],
                [
                    'name' => $slsName,
                    'slug' => $slsSlug,
                    'village_id' => $village->id,
                    'sls_code' => $props['kdsls'],                   // use full sls code
                    'geojson_path' => $slsFilename,
                    'base_map_id' => $baseMapId,
                ]
            );
        }
    }

    public static function importvillage ($geojsonPath, $baseMapId)
    {
        $geojsonData = json_decode(Storage::disk('public')->get($geojsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to decode GeoJSON: ' . json_last_error_msg());
            return;
        }

        // Inisialisasi array penampung desa
        $villages = [];

        // Loop fitur GeoJSON
        foreach ($geojsonData['features'] as $feature) {
            $props = $feature['properties'] ?? null;

            if (!$props) {
                Log::warning("Feature without properties found, skipped.");
                continue; // lewati fitur tanpa properti
            }

            // Ambil kode desa sebagai key grouping
            $villageCode = $props['iddesa'] ?? null;
            if (!$villageCode) {
                Log::warning("Feature without iddesa found, skipped.");
                continue; // lewati fitur tanpa kode desa
            }

            // Inisialisasi struktur desa jika belum ada
            if (!isset($villages[$villageCode])) {
                $villages[$villageCode] = [
                    'properties' => [
                        'kdkab' => $props['kdkab'] ?? '',
                        'nmkab' => $props['nmkab'] ?? '',
                        'kdkec' => $props['kdkec'] ?? '',
                        'nmkec' => $props['nmkec'] ?? '',
                        'kddesa' => $props['kddesa'] ?? '',
                        'nmdesa' => $props['nmdesa'] ?? '',
                    ],
                    'features' => [],
                ];
            }

            // Tambahkan fitur ke desa yang sesuai
            $villages[$villageCode]['features'][] = $feature;
        }

        // Proses simpan file dan update database untuk tiap desa
        foreach ($villages as $villageCode => $data) {
            $geojson = [
                'type' => 'FeatureCollection',
                'features' => $data['features'],
                'properties' => $data['properties'], // optional, kalau ingin disimpan di file
            ];

            // Tentukan path file
            $filePath = "villages/{$villageCode}.geojson";

            // Simpan file ke disk public storage
            Storage::disk('public')->put($filePath, json_encode($geojson));

            // Cari model Village sesuai kode desa
            $village = Village::where('code', $villageCode)->first();

            if ($village) {
                // Update kolom geojson_path
                $village->update([
                    'geojson_path' => $filePath,
                ]);

                Log::info("Desa {$village->name}: geojson_path updated to {$filePath}");
            } else {
                Log::warning("Village with code {$villageCode} not found in DB.");
            }
        }

        Log::info('Peta desa berhasil di-split dan disimpan.');
    }
}

