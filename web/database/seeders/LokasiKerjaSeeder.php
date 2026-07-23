<?php

namespace Database\Seeders;

use App\Models\LokasiKerja;
use Illuminate\Database\Seeder;

class LokasiKerjaSeeder extends Seeder
{
    public function run(): void
    {
        $lokasi = LokasiKerja::query()->orderBy('id')->first();
        if ($lokasi) {
            $lokasi->update(['is_aktif' => true]);

            return;
        }

        LokasiKerja::create([
            'nama_lokasi' => 'Kafe Satu Per Dua Kopitiam',
            'latitude' => -0.02630000,
            'longitude' => 109.34250000,
            'radius_meter' => 50,
            'is_aktif' => true,
        ]);
    }
}
