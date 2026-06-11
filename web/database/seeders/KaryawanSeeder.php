<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\LokasiKerja;

class KaryawanSeeder extends Seeder {
    public function run(): void {
        $lokasi = LokasiKerja::first();
        $admin = User::create(['name' => 'Administrator', 'username' => 'admin', 'email' => 'admin@kafe12.com', 'password' => bcrypt('password'), 'role' => 'admin', 'status' => 'aktif']);

        $data = [
            ['nama' => 'Andi Saputra', 'nik' => '7101010101010001', 'jabatan' => 'Barista', 'tarif' => 80000, 'tgl' => '2025-01-15', 'enrolled' => true],
            ['nama' => 'Sari Pratiwi', 'nik' => '7101010101010002', 'jabatan' => 'Kasir', 'tarif' => 85000, 'tgl' => '2024-08-01', 'enrolled' => true],
            ['nama' => 'Budi Santoso', 'nik' => '7101010101010003', 'jabatan' => 'Barista', 'tarif' => 80000, 'tgl' => '2025-03-10', 'enrolled' => true],
            ['nama' => 'Dewi Lestari', 'nik' => '7101010101010004', 'jabatan' => 'Pelayan', 'tarif' => 75000, 'tgl' => '2025-06-01', 'enrolled' => false],
            ['nama' => 'Rizky Pratama', 'nik' => '7101010101010005', 'jabatan' => 'Koki', 'tarif' => 90000, 'tgl' => '2024-11-20', 'enrolled' => true],
        ];

        foreach ($data as $i => $d) {
            $user = User::create(['name' => $d['nama'], 'username' => strtolower(str_replace(' ', '', $d['nama'])), 'email' => strtolower(str_replace(' ', '.', $d['nama'])).'@kafe12.com', 'password' => bcrypt('123456'), 'role' => 'karyawan', 'status' => $i === 4 ? 'nonaktif' : 'aktif']);
            Karyawan::create(['user_id' => $user->id, 'nik' => $d['nik'], 'nama_lengkap' => $d['nama'], 'jabatan' => $d['jabatan'], 'lokasi_kerja_id' => $lokasi->id, 'tarif_gaji_harian' => $d['tarif'], 'tgl_bergabung' => $d['tgl'], 'status' => $i === 4 ? 'nonaktif' : 'aktif']);
        }
    }
}