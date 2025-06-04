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
        // Only process the first sheet
        if ($rows->isEmpty()) {
            Log::error('No valid rows found in Excel file');
            throw ValidationException::withMessages([
                'file' => ['File Excel tidak memiliki data yang valid']
            ]);
        }

        // Get assignment and area
        $assignment = Assignment::with('area')->findOrFail($this->assignment_id);
        if (!$assignment->area) {
            throw ValidationException::withMessages([
                'file' => ['Assignment tidak memiliki wilayah yang valid']
            ]);
        }

        // Get village_id from assignment area
        $this->village_id = $assignment->area->id;

        // Filter out empty rows
        $validRows = $rows->filter(function ($row) {
            return !empty($row['nama_usaha']) && !empty($row['alamat']);
        });

        if ($validRows->isEmpty()) {
            Log::error('No valid rows found in Excel file');
            throw ValidationException::withMessages([
                'file' => ['File Excel tidak memiliki data yang valid']
            ]);
        }

        // Check required headers
        $missingHeaders = array_diff($this->requiredHeaders, array_keys($validRows->first()->toArray()));

        if (!empty($missingHeaders)) {
            Log::error('Missing required headers', ['headers' => $missingHeaders]);
            throw ValidationException::withMessages([
                'file' => ['File Excel tidak memiliki kolom yang diperlukan: ' . implode(', ', $missingHeaders)]
            ]);
        }

        // Process each row
        foreach ($validRows as $row) {
            try {
                // Get SLS ID with village filter
                $sls_id = Sls::where('name', $row['sls'])
                    ->where('village_id', $this->village_id)
                    ->value('id');

                // Get Business Category ID
                $code = substr($row['kategori_lapangan_usaha'], 0, 1);
                $business_category_id = BusinessCategory::where('code', $code)->value('id');

                if (!$business_category_id) {
                    throw new \Exception("Kategori usaha tidak ditemukan: {$row['kategori_lapangan_usaha']}");
                }

                //get online status
                $online_status = $row['apakah_memiliki_online'] ?? 'Tidak';

                //get pembinaan
                $pembinaan = $row['apakah_pemilik_mau_mengikuti_pembinaan'] ?? 'Tidak';

                //get usia
                $usia = $row['usia'] ?? null;
                if ($usia === '') {
                    $usia = null;
                }

                // Log all data before creating business
                Log::info('Creating business with data:', [
                    'row_data' => $row->toArray(),
                    'sls_id' => $sls_id,
                    'business_category_id' => $business_category_id,
                    'online_status' => $online_status,
                    'pembinaan' => $pembinaan,
                    'usia' => $usia,
                    'upload_user_id' => $this->upload?->user_id,
                    'upload_id' => $this->upload?->id,
                    'assignment_id' => $this->assignment_id,
                    'village_id' => $this->village_id
                ]);

                // Create business record
                $business = Business::create([
                    'name' => $row['nama_usaha'],
                    'address' => $row['alamat'],
                    'sls_id' => $sls_id ?? null,
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
                    'catatan' => $row['catatan_lantaibloksektor'] ?? null,
                    'user_id' =>$this->upload?->user_id,
                ]);

                // Handle certifications
                if (!empty($row['kepemilikan_sertifikat'])) {
                    $certificationNames = array_map('trim', explode('||', $row['kepemilikan_sertifikat']));
                    foreach ($certificationNames as $certificationName) {
                        if (empty($certificationName)) continue;

                        $certificate = Certification::where('name', 'like', '%' . $certificationName . '%')->first();
                        if ($certificate) {
                            $business->certifications()->attach($certificate->id);
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
