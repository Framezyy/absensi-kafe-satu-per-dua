<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        Shift::updateOrCreate(['nama' => 'Pagi'], ['jam_mulai' => '08:00:00', 'jam_selesai' => '16:00:00', 'toleransi_menit' => 15, 'is_aktif' => true]);
        Shift::updateOrCreate(['nama' => 'Malam'], ['jam_mulai' => '16:00:00', 'jam_selesai' => '00:00:00', 'toleransi_menit' => 15, 'is_aktif' => true]);
    }
}
