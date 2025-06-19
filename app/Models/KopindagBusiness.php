<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KopindagBusiness extends Model
{
    //

    // add fillable
    protected $fillable = [
        'uraian_jenis_proyek',
        'nib',
        'nama_perusahaan',
        'tanggal_terbit_oss',
        'status_penanaman_modal',
        'jenis_perusahaan',
        'risiko_proyek',
        'nama_proyek',
        'skala_usaha',
        'alamat_usaha',
        'kabupaten_kota_usaha',
        'district_id',
        'village_id',
        'sls_id',
        'tanggal_pengajuan_proyek',
        'kbli',
        'judul_kbli',
        'sektor_pembina',
        'nama_user',
        'email',
    ];
    // add guaded
    protected $guarded = ['id'];
    // add hidden
    protected $hidden = ['created_at', 'updated_at'];

    public function district()
    {
        return $this->belongsTo(\App\Models\District::class, 'district_id');
    }

    public function village()
    {
        return $this->belongsTo(\App\Models\Village::class, 'village_id');
    }

    public function sls()
    {
        return $this->belongsTo(\App\Models\Sls::class, 'sls_id');
    }
}
