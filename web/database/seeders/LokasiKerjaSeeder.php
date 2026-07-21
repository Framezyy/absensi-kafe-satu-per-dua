<?php

namespace Database\Seeders;

use App\Models\LokasiKerja;
use Illuminate\Database\Seeder;

class LokasiKerjaSeeder extends Seeder
{
    public function run(): void
    {
        LokasiKerja::updateOrCreate(['nama_lokasi' => 'Kafe Satu Per Dua Kopitiam'], [
            'nama_lokasi' => 'Kafe Satu Per Dua Kopitiam',
            'latitude' => -0.02630000,
            'longitude' => 109.34250000,
            'radius_meter' => 50,
            'is_aktif' => true,
        ]);
    }
}
