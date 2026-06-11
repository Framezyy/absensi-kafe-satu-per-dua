<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\LokasiKerja;

class LokasiKerjaSeeder extends Seeder {
    public function run(): void {
        LokasiKerja::create([
            'nama_lokasi' => 'Kafe Satu Per Dua Kopitiam',
            'latitude' => -0.02630000,
            'longitude' => 109.34250000,
            'radius_meter' => 50,
            'jam_masuk_standar' => '08:00:00',
            'toleransi_menit' => 15,
            'is_aktif' => true,
        ]);
    }
}