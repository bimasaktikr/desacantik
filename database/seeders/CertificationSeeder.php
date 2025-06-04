<?php

namespace Database\Seeders;

use App\Models\Certification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CertificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        Schema::disableForeignKeyConstraints();

        // Truncate existing data
        Certification::truncate();

        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();

        $certifications = [
            [
                'name' => 'NIB',
                'description' => 'Nomor Induk Berusaha',
            ],
            [
                'name' => 'SIUP',
                'description' => 'Surat Izin Usaha Perdagangan',
            ],
            [
                'name' => 'TDP',
                'description' => 'Tanda Daftar Perusahaan',
            ],
            [
                'name' => 'NPWP',
                'description' => 'Nomor Pokok Wajib Pajak',
            ],
            [
                'name' => 'SKU',
                'description' => 'Surat Keterangan Usaha',
            ],
            [
                'name' => 'Sertifikat Halal',
                'description' => 'Sertifikat Halal dari MUI',
            ],
            [
                'name' => 'PIRT',
                'description' => 'Produk Industri Rumah Tangga',
            ]
        ];

        foreach ($certifications as $certification) {
            Certification::create($certification);
        }
    }
}
