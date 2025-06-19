<?php

namespace App\Imports;

use App\Models\KopindagBusiness;
use App\Models\District;
use App\Models\Village;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class KopindagBusinessImport implements ToCollection, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    protected $rowCount = 0;

    public function __construct()
    {
        Log::info('KopindagBusinessImport __construct called');
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // $tanggalTerbit = $row->get('tanggal_terbit_oss') ? Carbon::parse($row->get('tanggal_terbit_oss')) : null;
            // $tanggalPengajuan = $row->get('tanggal_pengajuan_proyek') ? Carbon::parse($row->get('tanggal_pengajuan_proyek')) : null;

            $tanggalTerbit = $this->parseDate($row->get('tanggal_terbit_oss'));
            $tanggalPengajuan = $this->parseDate($row->get('tanggal_pengajuan_proyek'));


            $district = !empty($row->get('kecamatan_usaha'))
                ? District::whereRaw('LOWER(name) = ?', [strtolower($row->get('kecamatan_usaha'))])->first()
                : null;

            $village = !empty($row->get('kelurahan_usaha'))
                ? Village::whereRaw('LOWER(name) = ?', [strtolower($row->get('kelurahan_usaha'))])->first()
                : null;

            KopindagBusiness::updateOrCreate(
                ['nib' => $row->get('nib')],
                [
                    'uraian_jenis_proyek' => $row->get('uraian_jenis_proyek'),
                    'nama_perusahaan' => $row->get('nama_perusahaan'),
                    'tanggal_terbit_oss' => $tanggalTerbit,
                    'status_penanaman_modal' => $row->get('uraian_status_penanaman_modal'),
                    'jenis_perusahaan' => $row->get('uraian_jenis_perusahaan'),
                    'risiko_proyek' => $row->get('uraian_risiko_proyek'),
                    'nama_proyek' => $row->get('nama_proyek'),
                    'skala_usaha' => $row->get('uraian_skala_usaha'),
                    'alamat_usaha' => $row->get('alamat_usaha'),
                    'kabupaten_kota_usaha' => $row->get('kab_kota_usaha'),
                    'district_id' => $district?->id,
                    'village_id' => $village?->id,
                    'tanggal_pengajuan_proyek' => $tanggalPengajuan,
                    'kbli' => $row->get('kbli'),
                    'judul_kbli' => $row->get('judul_kbli'),
                    'sektor_pembina' => $row->get('sektor_pembina'),
                    'nama_user' => $row->get('nama_user'),
                    'email' => $row->get('email'),
                ]
            );
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    protected function parseDate($value)
    {
        if (!$value) {
            return null;
        }

        // Jika numeric (Excel date serial)
        if (is_numeric($value)) {
            try {
                return Date::excelToDateTimeObject($value);
            } catch (\Exception $e) {
                Log::error('Failed parsing Excel serial date', ['value' => $value, 'error' => $e->getMessage()]);
                return null;
            }
        }

        // Coba format manual
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Exception $e) {
                continue;
            }
        }

        // Terakhir, fallback ke parse otomatis Carbon
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            \Log::error('Carbon::parse failed', ['value' => $value, 'error' => $e->getMessage()]);
            return null;
        }
    }
}
