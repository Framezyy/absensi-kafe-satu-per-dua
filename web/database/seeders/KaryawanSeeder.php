<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\LokasiKerja;
use App\Models\Shift;
use App\Models\User;
use App\Services\DailyScheduleMaterializer;
use Illuminate\Database\Seeder;

class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        $lokasi = LokasiKerja::first();
        $pagi = Shift::where('nama', 'Pagi')->firstOrFail();
        $malam = Shift::where('nama', 'Malam')->firstOrFail();
        User::firstOrCreate(['username' => 'admin'], ['name' => 'Administrator', 'email' => 'admin@kafe12.com', 'password' => 'kafesatuperdua2026', 'role' => 'admin', 'status' => 'aktif']);

        $data = [
            ['nama' => 'Andi Saputra', 'jabatan' => 'Barista', 'tgl' => '2025-01-15', 'status' => 'aktif', 'shift' => $pagi->id],
            ['nama' => 'Sari Pratiwi', 'jabatan' => 'Kasir', 'tgl' => '2024-08-01', 'status' => 'aktif', 'shift' => $pagi->id],
            ['nama' => 'Budi Santoso', 'jabatan' => 'Barista', 'tgl' => '2025-03-10', 'status' => 'aktif', 'shift' => $malam->id],
            ['nama' => 'Dewi Lestari', 'jabatan' => 'Pelayan', 'tgl' => '2025-06-01', 'status' => 'aktif', 'shift' => $malam->id],
            ['nama' => 'Rizky Pratama', 'jabatan' => 'Koki', 'tgl' => '2024-11-20', 'status' => 'nonaktif', 'shift' => $pagi->id],
        ];

        foreach ($data as $d) {
            $username = strtolower(str_replace(' ', '', $d['nama']));
            $user = User::firstOrCreate(['username' => $username], ['name' => $d['nama'], 'email' => strtolower(str_replace(' ', '.', $d['nama'])).'@kafe12.com', 'password' => '123456789012', 'role' => 'karyawan', 'status' => $d['status']]);
            Karyawan::firstOrCreate(['user_id' => $user->id], ['nama_lengkap' => $d['nama'], 'jabatan' => $d['jabatan'], 'lokasi_kerja_id' => $lokasi->id, 'default_shift_id' => $d['shift'], 'tarif_per_jam' => config('payroll.hourly_rate', 10000), 'tgl_bergabung' => $d['tgl'], 'status' => $d['status']]);
        }

        app(DailyScheduleMaterializer::class)->materializeWindow(today());
    }
}
