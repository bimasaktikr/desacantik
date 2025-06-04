<?php

namespace App\Imports;

use App\Models\Assignment;
use App\Models\AssignmentUpload;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Certification;
use App\Models\Sls;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Lab404\Impersonate\Services\ImpersonateManager;

class BusinessesImport implements ToCollection, WithHeadingRow, WithStartRow, WithValidation, WithMultipleSheets
{
    protected $assignment_id;
    protected $upload;
    protected $processedRows = 0;
    protected $successRows = 0;
    protected $failedRows = 0;
    protected $village_id;

    public function __construct($assignment_id, AssignmentUpload $upload = null)
    {
        $this->assignment_id = $assignment_id;
        $this->upload = $upload;
    }

    // protected function getUserId()
    // {
    //     $impersonateManager = app()->make(ImpersonateManager::class);

    //     if ($impersonateManager->isImpersonating()) {
    //         // Return the impersonated user's ID
    //         return $impersonateManager->getImpersonatorId();
    //     }

    //     // Return the actual uploader's ID
    //     return $this->upload->user_id;
    // }

    public function sheets(): array
    {
        return [
            0 => $this, // Only process the first sheet
        ];
    }

    protected $requiredHeaders = [
        'nama_usaha',
        'alamat',
        'status_bangunan_usaha',
        'kategori_lapangan_usaha',
        'deskripsi_aktifitas',
        'sls',
        'latitude',
        'longitude',
        'catatan_lantaibloksektor',
        'apakah_pertokoan'
    ];

    public function headingRow(): int
    {
        return 1;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function rules(): array
    {
        return [
            'nama_usaha' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'status_bangunan_usaha' => 'required|in:Tetap,Tidak Tetap',
            'kategori_lapangan_usaha' => 'required|string',
            'deskripsi_aktifitas' => 'required|string',
            'sls' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'no_handphone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'nama_pemilik_usaha' => 'nullable|string|max:255',
            'jenis_kelamin' => 'nullable|in:Laki-Laki,Perempuan',
            'usia' => 'nullable|string',
            'apakah_memiliki_online' => 'nullable|in:Ya,Tidak',
            'apakah_pemilik_mau_mengikuti_pembinaan' => 'nullable|in:Ya,Tidak',
            'catatan_lantaibloksektor' => 'nullable|string|max:255',
            'apakah_pertokoan' => 'nullable|in:Ya,Tidak',
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
            'sls.required' => 'Kolom SLS harus diisi',
            'latitude.numeric' => 'Latitude harus berupa angka',
            'latitude.between' => 'Latitude harus antara -90 dan 90',
            'longitude.numeric' => 'Longitude harus berupa angka',
            'longitude.between' => 'Longitude harus antara -180 dan 180',
            'email.email' => 'Format email tidak valid',
            'jenis_kelamin.in' => 'Jenis kelamin harus Laki-Laki atau Perempuan',
            'apakah_pemilik_mau_mengikuti_pembinaan.in' => 'Pembinaan harus Ya atau Tidak',
            'apakah_memiliki_online.in' => 'Status online harus Ya atau Tidak',
            'apakah_pertokoan.in' => 'Pertokoan harus Ya atau Tidak',
        ];
    }

    protected function updateProgress()
    {
        if (!$this->upload) {
            return;
        }

        try {
            $this->upload->updateProgress(
                $this->processedRows,
                $this->successRows,
                $this->failedRows
            );
        } catch (\Exception $e) {
            Log::error('Error updating progress: ' . $e->getMessage());
        }
    }

    public function collection(Collection $rows)
    {
        // Get assignment and village_id first
        $assignment = Assignment::with('area')->findOrFail($this->assignment_id);
        if (!$assignment->area) {
            throw ValidationException::withMessages([
                'file' => ['Assignment tidak memiliki wilayah yang valid']
            ]);
        }
        $this->village_id = $assignment->area->id;

        // Get all valid SLS for this village
        $validSls = Sls::where('village_id', $this->village_id)
                      ->pluck('name')
                      ->toArray();

        Log::info('Starting import with village data', [
            'village_id' => $this->village_id,
            'valid_sls_list' => $validSls
        ]);

        // Get default SLS for this village (first SLS in the village)
        $defaultSls = Sls::where('village_id', $this->village_id)->first();
        if (!$defaultSls) {
            throw ValidationException::withMessages([
                'file' => ['Wilayah ini tidak memiliki SLS yang valid']
            ]);
        }

        Log::info('Default SLS found', [
            'default_sls_id' => $defaultSls->id,
            'default_sls_name' => $defaultSls->name
        ]);

        foreach ($rows as $index => $row) {
            try {
                // Log raw row data
                Log::info('Processing row ' . ($index + 1), [
                    'raw_row' => $row->toArray(),
                    'sls_value' => $row['sls'] ?? 'null',
                    'sls_type' => gettype($row['sls']),
                    'sls_length' => $row['sls'] ? strlen($row['sls']) : 0
                ]);

                // Convert usia to integer if it's a string
                $usia = is_numeric($row['usia']) ? (int)$row['usia'] : null;

                // Convert online status
                $online_status = $row['apakah_memiliki_online'] === 'Ya' ? 'Ya' : 'Tidak';

                // Convert pembinaan status
                $pembinaan = $row['apakah_pemilik_mau_mengikuti_pembinaan'] === 'Ya' ? 'Ya' : 'Tidak';

                // Convert pertokoan status
                $pertokoan = strtolower($row['apakah_pertokoan'] ?? 'tidak');

                // Get SLS ID - now we search within the assignment's village
                $sls_id = $defaultSls->id; // Default to first SLS in village
                if (!empty($row['sls'])) {
                    // Log the SLS search attempt
                    Log::info('Attempting to find SLS', [
                        'search_sls' => $row['sls'],
                        'village_id' => $this->village_id,
                        'search_query' => [
                            'name' => $row['sls'],
                            'village_id' => $this->village_id
                        ]
                    ]);

                    $sls = Sls::where('name', $row['sls'])
                             ->where('village_id', $this->village_id)
                             ->first();

                    // Log the SLS search result
                    Log::info('SLS search result', [
                        'found' => !is_null($sls),
                        'sls_id' => $sls ? $sls->id : null,
                        'sls_name' => $sls ? $sls->name : null,
                        'search_term' => $row['sls']
                    ]);

                    if ($sls) {
                        $sls_id = $sls->id;
                        Log::info('Using found SLS', [
                            'sls_id' => $sls_id,
                            'sls_name' => $sls->name
                        ]);
                    } else {
                        // Log warning but use default SLS
                        Log::warning('SLS tidak valid, menggunakan SLS default', [
                            'sls_name' => $row['sls'],
                            'village_id' => $this->village_id,
                            'valid_sls' => $validSls,
                            'business_name' => $row['nama_usaha'],
                            'business_address' => $row['alamat'],
                            'default_sls_id' => $defaultSls->id,
                            'default_sls_name' => $defaultSls->name,
                            'search_term' => $row['sls'],
                            'search_term_length' => strlen($row['sls']),
                            'search_term_type' => gettype($row['sls'])
                        ]);

                        // Debug: Check if SLS exists with different case
                        $caseInsensitiveSls = Sls::whereRaw('LOWER(name) = ?', [strtolower($row['sls'])])
                                               ->where('village_id', $this->village_id)
                                               ->first();

                        if ($caseInsensitiveSls) {
                            Log::info('Found SLS with different case', [
                                'original_search' => $row['sls'],
                                'found_sls' => $caseInsensitiveSls->name,
                                'found_sls_id' => $caseInsensitiveSls->id
                            ]);
                        }
                    }
                } else {
                    Log::warning('SLS kosong, menggunakan SLS default', [
                        'business_name' => $row['nama_usaha'],
                        'business_address' => $row['alamat'],
                        'default_sls_id' => $defaultSls->id,
                        'default_sls_name' => $defaultSls->name,
                        'row_data' => $row->toArray()
                    ]);
                }

                // Get business category ID
                $business_category_id = null;
                if (!empty($row['kategori_lapangan_usaha'])) {
                    // Log the category search attempt
                    Log::info('Attempting to find business category', [
                        'search_category' => $row['kategori_lapangan_usaha'],
                        'search_term_type' => gettype($row['kategori_lapangan_usaha']),
                        'search_term_length' => strlen($row['kategori_lapangan_usaha'])
                    ]);

                    // Get all categories for debugging
                    $allCategories = BusinessCategory::all()->pluck('description')->toArray();
                    Log::info('Available categories', [
                        'all_categories' => $allCategories
                    ]);

                    // Get first character as code
                    $excelCode = substr(trim($row['kategori_lapangan_usaha']), 0, 1);

                    Log::info('Extracted category code', [
                        'excel_code' => $excelCode,
                        'full_category' => $row['kategori_lapangan_usaha']
                    ]);

                    // Find category by code
                    $category = BusinessCategory::where('code', $excelCode)->first();

                    // Log the category search result
                    Log::info('Business category search result', [
                        'found' => !is_null($category),
                        'category_id' => $category ? $category->id : null,
                        'category_code' => $category ? $category->code : null,
                        'category_name' => $category ? $category->description : null,
                        'search_term' => $row['kategori_lapangan_usaha'],
                        'excel_code' => $excelCode
                    ]);

                    if ($category) {
                        $business_category_id = $category->id;
                        Log::info('Using found business category', [
                            'category_id' => $business_category_id,
                            'category_code' => $category->code,
                            'category_name' => $category->description
                        ]);
                    } else {
                        Log::warning('Business category not found', [
                            'search_term' => $row['kategori_lapangan_usaha'],
                            'excel_code' => $excelCode,
                            'business_name' => $row['nama_usaha'],
                            'business_address' => $row['alamat']
                        ]);
                    }
                } else {
                    Log::warning('Business category is empty', [
                        'business_name' => $row['nama_usaha'],
                        'business_address' => $row['alamat']
                    ]);
                }

                // Try to find existing business by point_id first
                $business = null;
                if (!empty($row['point_id'])) {
                    $business = Business::where('point_id', $row['point_id'])
                                     ->where('user_id', $this->upload?->user_id)
                                     ->where('village_id', $this->village_id)
                                     ->first();
                }

                // If not found by point_id, try fuzzy matching by name and address
                if (!$business) {
                    $business = Business::where('user_id', $this->upload?->user_id)
                                     ->where('village_id', $this->village_id)
                                     ->where(function($query) use ($row) {
                                         $query->where('name', 'like', '%' . $row['nama_usaha'] . '%')
                                               ->where('address', 'like', '%' . $row['alamat'] . '%');
                                     })->first();
                }

                if ($business) {
                    // Update existing business
                    $business->update([
                        'name' => $row['nama_usaha'],
                        'address' => $row['alamat'],
                        'sls_id' => $sls_id, // Always use the determined SLS ID
                        'village_id' => $this->village_id,
                        'latitude' => $row['latitude'] ?? $business->latitude,
                        'longitude' => $row['longitude'] ?? $business->longitude,
                        'status_bangunan' => $row['status_bangunan_usaha'],
                        'business_category_id' => $business_category_id ?? $business->business_category_id,
                        'description' => $row['deskripsi_aktifitas'],
                        'phone' => $row['no_handphone'] ?? $business->phone,
                        'email' => $row['email'] ?? $business->email,
                        'owner_name' => $row['nama_pemilik_usaha'] ?? $business->owner_name,
                        'owner_gender' => $row['jenis_kelamin'] ?? $business->owner_gender,
                        'owner_age' => $usia ?? $business->owner_age,
                        'online_status' => $online_status,
                        'pembinaan' => $pembinaan,
                        'pertokoan' => $pertokoan,
                        'catatan' => $row['catatan_lantaibloksektor'] ?? $business->catatan,
                        'user_id' => $this->upload?->user_id ?? $business->user_id,
                    ]);

                    Log::info('Updated existing business', [
                        'business_id' => $business->id,
                        'point_id' => $row['point_id'] ?? null,
                        'village_id' => $this->village_id,
                        'sls_id' => $sls_id
                    ]);
                } else {
                    // Create new business
                    $business = Business::create([
                        'point_id' => $row['point_id'] ?? null,
                        'name' => $row['nama_usaha'],
                        'address' => $row['alamat'],
                        'sls_id' => $sls_id, // Always use the determined SLS ID
                        'village_id' => $this->village_id,
                        'latitude' => $row['latitude'] ?? null,
                        'longitude' => $row['longitude'] ?? null,
                        'status_bangunan' => $row['status_bangunan_usaha'],
                        'business_category_id' => $business_category_id,
                        'description' => $row['deskripsi_aktifitas'],
                        'phone' => $row['no_handphone'] ?? null,
                        'email' => $row['email'] ?? null,
                        'owner_name' => $row['nama_pemilik_usaha'] ?? null,
                        'owner_gender' => $row['jenis_kelamin'] ?? null,
                        'owner_age' => $usia,
                        'online_status' => $online_status,
                        'pembinaan' => $pembinaan,
                        'pertokoan' => $pertokoan,
                        'catatan' => $row['catatan_lantaibloksektor'] ?? null,
                        'user_id' => $this->upload?->user_id,
                    ]);

                    Log::info('Created new business', [
                        'business_id' => $business->id,
                        'point_id' => $row['point_id'] ?? null,
                        'village_id' => $this->village_id,
                        'sls_id' => $sls_id
                    ]);
                }

                // Handle certifications
                if (!empty($row['kepemilikan_sertifikat'])) {
                    $certificationNames = array_map('trim', explode('||', $row['kepemilikan_sertifikat']));
                    foreach ($certificationNames as $certificationName) {
                        if (empty($certificationName)) continue;

                        $certificate = Certification::where('name', 'like', '%' . $certificationName . '%')->first();
                        if ($certificate) {
                            $business->certifications()->syncWithoutDetaching([$certificate->id]);
                        }
                    }
                }

                $this->successRows++;
            } catch (\Exception $e) {
                Log::error('Error processing row', [
                    'row' => $row->toArray(),
                    'error' => $e->getMessage()
                ]);
                $this->failedRows++;
            }

            $this->processedRows++;

            // Update progress every 10 rows
            if ($this->upload && $this->processedRows % 10 === 0) {
                $this->upload->updateProgress(
                    $this->processedRows,
                    $this->successRows,
                    $this->failedRows
                );
            }
        }

        // Final progress update
        if ($this->upload) {
            $this->upload->updateProgress(
                $this->processedRows,
                $this->successRows,
                $this->failedRows
            );
        }
    }
}
