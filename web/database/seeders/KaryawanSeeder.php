<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\LokasiKerja;
use App\Models\User;
use Illuminate\Database\Seeder;

class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        $lokasi = LokasiKerja::first();
        $admin = User::create(['name' => 'Administrator', 'username' => 'admin', 'email' => 'admin@kafe12.com', 'password' => bcrypt('kafesatuperdua2026'), 'role' => 'admin', 'status' => 'aktif']);

        $data = [
            ['nama' => 'Andi Saputra', 'jabatan' => 'Barista', 'tgl' => '2025-01-15'],
            ['nama' => 'Sari Pratiwi', 'jabatan' => 'Kasir', 'tgl' => '2024-08-01'],
            ['nama' => 'Budi Santoso', 'jabatan' => 'Barista', 'tgl' => '2025-03-10'],
            ['nama' => 'Dewi Lestari', 'jabatan' => 'Pelayan', 'tgl' => '2025-06-01'],
            ['nama' => 'Rizky Pratama', 'jabatan' => 'Koki', 'tgl' => '2024-11-20'],
        ];

        foreach ($data as $i => $d) {
            $user = User::create(['name' => $d['nama'], 'username' => strtolower(str_replace(' ', '', $d['nama'])), 'email' => strtolower(str_replace(' ', '.', $d['nama'])).'@kafe12.com', 'password' => bcrypt('123456'), 'role' => 'karyawan', 'status' => $i === 4 ? 'nonaktif' : 'aktif']);
            Karyawan::create(['user_id' => $user->id, 'nama_lengkap' => $d['nama'], 'jabatan' => $d['jabatan'], 'lokasi_kerja_id' => $lokasi->id, 'tarif_per_jam' => config('payroll.hourly_rate', 10000), 'tgl_bergabung' => $d['tgl'], 'status' => $i === 4 ? 'nonaktif' : 'aktif']);
        }
    }
}
