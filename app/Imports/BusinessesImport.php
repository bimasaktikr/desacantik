<?php

namespace App\Imports;

use App\Models\Assignment;
use App\Models\AssignmentUpload;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Certification;
use App\Models\Sls;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Validation\ValidationException;

class BusinessesImport implements ToCollection, WithHeadingRow, WithStartRow, WithValidation, WithMultipleSheets
{
    protected $assignment_id;
    protected $upload;
    protected $processedRows = 0;
    protected $successRows = 0;
    protected $failedRows = 0;
    protected $village_id;
    protected $failures = [];

    public function __construct($assignment_id, AssignmentUpload $upload = null)
    {
        $this->assignment_id = $assignment_id;
        $this->upload = $upload;
    }

    public function sheets(): array
    {
        return [0 => $this];
    }

    public function startRow(): int
    {
        return 2;
    }

    public function rules(): array
    {
        // Rules now cover all potential columns from the most detailed file
        return [
            // Business Data
            'nama_usaha' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'status_bangunan_usaha' => 'required|in:Tetap,Tidak Tetap',
            'kategori_lapangan_usaha' => 'required|string',
            'deskripsi_aktifitas' => 'required|string',
            'sls' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'catatan_lantaibloksektor' => 'required|string',
            'apakah_pertokoan' => 'nullable|in:Ya,Tidak',
            'nama_pemilik_usaha' => 'nullable|string|max:255',
            'jenis_kelamin' => 'nullable|in:Laki-Laki,Perempuan',
            'usia' => 'nullable|string',
            'no_handphone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'apakah_memiliki_online' => 'nullable|in:Ya,Tidak',
            'apakah_pemilik_mau_mengikuti_pembinaan' => 'nullable|in:Ya,Tidak',
            'kepemilikan_sertifikat' => 'nullable|string',

            // GIS/GPS Data
            'id' => 'nullable', // This is the feature ID from the file
            'remarks' => 'nullable|string',
            'time' => 'nullable', // Remove strict date format validation
            'geometry' => 'nullable|string',
            'x' => 'nullable|numeric',
            'y' => 'nullable|numeric',
            'elevation' => 'nullable|numeric',
            'ortho_height' => 'nullable|numeric',
            'instrument_ht' => 'nullable|numeric',
            'fix_id' => 'nullable', // Allow any type for fix_id
            'speed' => 'nullable|numeric',
            'bearing' => 'nullable|numeric',
            'horizontal_accuracy' => 'nullable|numeric',
            'vertical_accuracy' => 'nullable|numeric',
            'pdop' => 'nullable|numeric',
            'hdop' => 'nullable|numeric',
            'vdop' => 'nullable|numeric',
            'satellites_in_view' => 'nullable', // Allow any type
            'satellites_in_use' => 'nullable', // Allow any type
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_usaha.required' => 'Kolom nama usaha harus diisi',
            'alamat.required' => 'Kolom alamat harus diisi',
            'status_bangunan_usaha.required' => 'Kolom status bangunan harus diisi',
            'status_bangunan_usaha.in' => 'Status bangunan harus Tetap atau Tidak Tetap',
            'kategori_lapangan_usaha.required' => 'Kolom kategori usaha harus diisi',
            'deskripsi_aktifitas.required' => 'Kolom deskripsi harus diisi',
            'latitude.required' => 'Kolom latitude harus diisi',
            'latitude.numeric' => 'Latitude harus berupa angka',
            'latitude.between' => 'Latitude harus antara -90 dan 90',
            'longitude.required' => 'Kolom longitude harus diisi',
            'longitude.numeric' => 'Longitude harus berupa angka',
            'longitude.between' => 'Longitude harus antara -180 dan 180',
            'email.email' => 'Format email tidak valid',
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-Laki atau Perempuan',
            'apakah_pemilik_mau_mengikuti_pembinaan.in' => 'Pembinaan harus Ya atau Tidak',
            'apakah_memiliki_online.in' => 'Status online harus Ya atau Tidak',
            'apakah_pertokoan.in' => 'Pertokoan harus Ya atau Tidak',
            'sls.required' => 'Kolom SLS harus diisi',
            'catatan_lantaibloksektor.required' => 'Kolom Catatan Lantai Blok Sektor harus diisi',
        ];
    }

    public function collection(Collection $rows)
    {
        $assignment = Assignment::with('area')->findOrFail($this->assignment_id);
        $this->village_id = $assignment->area->id;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // Assuming start row is 2
            try {
                // Convert '-' in jenis_kelamin, apakah_memiliki_online, and apakah_pemilik_mau_mengikuti_pembinaan to null
                if (isset($row['jenis_kelamin']) && trim($row['jenis_kelamin']) === '-') {
                    $row['jenis_kelamin'] = null;
                }
                if (isset($row['apakah_memiliki_online']) && trim($row['apakah_memiliki_online']) === '-') {
                    $row['apakah_memiliki_online'] = null;
                }
                if (isset($row['apakah_pemilik_mau_mengikuti_pembinaan']) && trim($row['apakah_pemilik_mau_mengikuti_pembinaan']) === '-') {
                    $row['apakah_pemilik_mau_mengikuti_pembinaan'] = null;
                }
                // Convert numeric fields to appropriate types
                $data = [
                    // Business Data
                    'name' => $row['nama_usaha'],
                    'address' => $row['alamat'],
                    'village_id' => $this->village_id,
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'status_bangunan' => $row['status_bangunan_usaha'],
                    'description' => $row['deskripsi_aktifitas'],
                    'phone' => $row['no_handphone'] ?? null,
                    'email' => $row['email'] ?? null,
                    'owner_name' => $row['nama_pemilik_usaha'] ?? null,
                    'owner_gender' => $row['jenis_kelamin'] ?? null,
                    'owner_age' => is_numeric($row['usia']) ? (int)$row['usia'] : null,
                    'online_status' => $row['apakah_memiliki_online'] ?? null ,
                    'pembinaan' => $row['apakah_pemilik_mau_mengikuti_pembinaan'] ?? null,
                    'pertokoan' => strtolower($row['apakah_pertokoan'] ?? null ),
                    'catatan' => $row['catatan_lantaibloksektor'] ?? $row['catatan_lantai_blok_sektor'] ?? null,
                    'user_id' => $this->upload?->user_id,

                    // GIS/GPS Data
                    'point_id' => $row['id'] ?? null,
                    'remarks' => $row['remarks'] ?? null,
                    'time' => isset($row['time']) ? $this->parseDateTime($row['time']) : null,
                    'geometry' => $row['geometry'] ?? null,
                    'gis_x' => $row['x'] ?? null,
                    'gis_y' => $row['y'] ?? null,
                    'elevation' => $row['elevation'] ?? null,
                    'ortho_height' => $row['ortho_height'] ?? null,
                    'instrument_ht' => $row['instrument_ht'] ?? null,
                    'fix_id' => $row['fix_id'] ? (string)$row['fix_id'] : null, // Convert to string
                    'speed' => $row['speed'] ?? null,
                    'bearing' => $row['bearing'] ?? null,
                    'horizontal_accuracy' => $row['horizontal_accuracy'] ?? null,
                    'vertical_accuracy' => $row['vertical_accuracy'] ?? null,
                    'pdop' => $row['pdop'] ?? null,
                    'hdop' => $row['hdop'] ?? null,
                    'vdop' => $row['vdop'] ?? null,
                    'satellites_in_view' => is_numeric($row['satellites_in_view']) ? (int)$row['satellites_in_view'] : null,
                    'satellites_in_use' => is_numeric($row['satellites_in_use']) ? (int)$row['satellites_in_use'] : null,
                ];

                // Handle SLS ID
                $sls = !empty($row['sls']) ? Sls::where('name', 'like', '%' . $row['sls'] . '%')->where('village_id', $this->village_id)->first() : null;
                if (!$sls) {
                    $this->failures[] = "Baris {$rowNumber}: SLS '{$row['sls']}' tidak ditemukan untuk desa ini.";
                    $this->failedRows++;
                    $this->processedRows++;
                    continue;
                }
                $data['sls_id'] = $sls->id;

                // Handle Business Category ID
                $category = null;
                if (!empty($row['kategori_lapangan_usaha'])) {
                    $excelCode = substr(trim($row['kategori_lapangan_usaha']), 0, 1);
                    $category = BusinessCategory::where('code', $excelCode)->first();
                }
                $data['business_category_id'] = $category ? $category->id : null;


                $business = null;
                // Update or Create Logic
                if (!$business) {
                    $business = Business::where('user_id', $this->upload?->user_id)
                                     ->where('village_id', $this->village_id)
                                     ->where(function($query) use ($row) {
                                         $query->where('name', 'like', '%' . $row['nama_usaha'] . '%')
                                               ->where('address', 'like', '%' . $row['alamat'] . '%');
                                     })->first();
                }

                if ($business) {
                    $business->update($data);
                } else {
                    $business = Business::create($data);
                }

                // Handle certifications
                if (!empty($row['kepemilikan_sertifikat'])) {
                    $certificationNames = array_map('trim', explode('||', $row['kepemilikan_sertifikat']));
                    $certificateIds = Certification::whereIn('name', $certificationNames)->pluck('id');
                    if ($certificateIds->isNotEmpty()) {
                        $business->certifications()->syncWithoutDetaching($certificateIds);
                    }
                }

                $this->successRows++;
            } catch (ValidationException $e) {
                $this->failedRows++;
                $errors = $e->errors();
                foreach ($errors as $attribute => $errorMessages) {
                    $this->failures[] = "Baris {$rowNumber}: " . implode(', ', $errorMessages);
                }
            } catch (\Exception $e) {
                $this->failedRows++;
                $this->failures[] = "Baris {$rowNumber}: Terjadi error - " . $e->getMessage();
                Log::error('Error processing row: ' . $rowNumber, [
                    'row' => $row->toArray(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            $this->processedRows++;
        }

        $this->upload?->updateProgress($this->processedRows, $this->successRows, $this->failedRows, $this->failures);
    }

    protected function parseDateTime($dateTime)
    {
        if (empty($dateTime)) {
            return null;
        }

        try {
            // Handle Excel's date format with timezone (e.g., "05/18/2025 21:00:36.000 GMT+07:00")
            if (strpos($dateTime, 'GMT') !== false) {
                // Extract the date part and timezone
                $parts = explode(' GMT', $dateTime);
                $datePart = trim($parts[0]);
                $timezone = 'GMT' . trim($parts[1]);

                // Parse the date part
                $date = Carbon::createFromFormat('m/d/Y H:i:s.u', $datePart);

                // Set the timezone
                $date->setTimezone($timezone);

                return $date;
            }

            // Fallback to Carbon's flexible parsing
            return Carbon::parse($dateTime);
        } catch (\Exception $e) {
            Log::warning('Failed to parse date time', [
                'value' => $dateTime,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
