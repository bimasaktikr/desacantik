<?php

namespace App\Exports;

use App\Models\Business;
use App\Models\User;
use App\Models\Assignment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BusinessDataExport implements FromQuery, WithHeadings, WithMapping
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function query()
    {
        $query = Business::query()->with(['businessCategory', 'user', 'village', 'sls']);

        $query->where('final_flag', true);

        if ($this->user->roles->contains('name', 'Employee') || $this->user->roles->contains('name', 'Mahasiswa')) {
            $assignedVillageIds = Assignment::where('user_id', $this->user->id)
                ->where('area_type', 'App\\Models\\Village')
                ->pluck('area_id')
                ->toArray();

            if (!empty($assignedVillageIds)) {
                $query->whereIn('village_id', $assignedVillageIds);
            } else {
                // If user has no assignments, return no results.
                $query->whereRaw('1 = 0');
            }
        }
        // Super admins will get all results, so no extra condition is needed.

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Point ID',
            'Geometry',
            'Latitude',
            'Longitude',
            'GIS X',
            'GIS Y',
            'Elevation',
            'Ortho Height',
            'Instrument Ht',
            'Fix ID',
            'Horizontal Accuracy',
            'Vertical Accuracy',
            'PDOP',
            'HDOP',
            'VDOP',
            'Satellites in View',
            'Satellites in Use',
            'Speed',
            'Bearing',
            'Time',
            'Remarks',
            'Kelurahan',
            'SLS',
            'Alamat Lengkap',
            'Nama Usaha',
            'Deskripsi Aktifitas',
            'Status Bangunan Usaha',
            'Sektor',
            'Catatan (Lantai/Blok/Sektor)',
            'Phone',
            'Email',
            'Nama Pemilik',
            'Jenis Kelamin',
            'Umur',
            'Apakah Memiliki Online',
            'Apakah Mengikuti Pembinaan',
            'Apakah Pertokoan',
            'Nama Mahasiswa',
        ];
    }

    /**
     * @param Business $business
     */
    public function map($business): array
    {
        return [
            $business->id,
            $business->point_id,
            $business->geometry,
            $business->latitude,
            $business->longitude,
            $business->gis_x,
            $business->gis_y,
            $business->elevation,
            $business->ortho_height,
            $business->instrument_ht,
            $business->fix_id,
            $business->horizontal_accuracy,
            $business->vertical_accuracy,
            $business->pdop,
            $business->hdop,
            $business->vdop,
            $business->satellites_in_view,
            $business->satellites_in_use,
            $business->speed,
            $business->bearing,
            $business->time,
            $business->remarks,
            $business->village?->name,
            $business->sls?->name,
            $business->address,
            $business->name,
            $business->description,
            $business->status_bangunan,
            $business->businessCategory ? $business->businessCategory->code . '. ' . $business->businessCategory->description : null,
            $business->catatan,
            $business->phone,
            $business->email,
            $business->owner_name,
            $business->owner_gender ?? '-',
            $business->owner_age,
            $business->online_status ?? '-',
            $business->pembinaan,
            $business->pertokoan ?? '-',
            $business->user?->name,
        ];
    }
}
