<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\LokasiKerja;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payroll_uses_only_completed_paid_minutes(): void
    {
        $location = LokasiKerja::create(['nama_lokasi' => 'Test', 'latitude' => -6.2, 'longitude' => 106.8, 'radius_meter' => 100, 'is_aktif' => true]);
        $user = User::create(['name' => 'Pegawai', 'username' => 'pegawai', 'email' => 'pegawai@test.local', 'password' => 'password', 'role' => 'karyawan', 'status' => 'aktif']);
        $employee = Karyawan::create(['user_id' => $user->id, 'nama_lengkap' => 'Pegawai', 'jabatan' => 'Barista', 'lokasi_kerja_id' => $location->id, 'tarif_per_jam' => 10000, 'tgl_bergabung' => '2026-01-01', 'status' => 'aktif']);
        Absensi::create(['karyawan_id' => $employee->id, 'tanggal' => '2026-07-01', 'clock_in_at' => '2026-07-01 08:00:00', 'clock_out_at' => '2026-07-01 16:00:00', 'jam_masuk' => '08:00', 'jam_pulang' => '16:00', 'status_kehadiran' => 'selesai', 'paid_minutes' => 480]);
        Absensi::create(['karyawan_id' => $employee->id, 'tanggal' => '2026-07-02', 'clock_in_at' => '2026-07-02 08:00:00', 'jam_masuk' => '08:00', 'status_kehadiran' => 'tidak_lengkap', 'paid_minutes' => 0]);

        app(PayrollService::class)->generate(7, 2026);
        $this->assertDatabaseHas('penggajian', ['karyawan_id' => $employee->id, 'total_paid_minutes' => 480, 'total_tidak_lengkap' => 1, 'total_gaji' => 80000]);
    }

    public function test_payroll_rounds_each_shift_before_summing(): void
    {
        $location = LokasiKerja::create(['nama_lokasi' => 'Test', 'latitude' => -6.2, 'longitude' => 106.8, 'radius_meter' => 100, 'is_aktif' => true]);
        $user = User::create(['name' => 'Pegawai', 'username' => 'pegawai2', 'email' => 'pegawai2@test.local', 'password' => 'password', 'role' => 'karyawan', 'status' => 'aktif']);
        $employee = Karyawan::create(['user_id' => $user->id, 'nama_lengkap' => 'Pegawai', 'jabatan' => 'Barista', 'lokasi_kerja_id' => $location->id, 'tarif_per_jam' => 10000, 'tgl_bergabung' => '2026-01-01', 'status' => 'aktif']);
        foreach (['01', '02'] as $day) {
            Absensi::create(['karyawan_id' => $employee->id, 'tanggal' => "2026-07-{$day}", 'clock_in_at' => "2026-07-{$day} 08:00:00", 'clock_out_at' => "2026-07-{$day} 15:40:00", 'jam_masuk' => '08:00', 'jam_pulang' => '15:40', 'status_kehadiran' => 'selesai', 'paid_minutes' => 460]);
        }

        app(PayrollService::class)->generate(7, 2026);

        $this->assertDatabaseHas('penggajian', ['karyawan_id' => $employee->id, 'total_paid_minutes' => 920, 'total_gaji' => 153334]);
    }
}
