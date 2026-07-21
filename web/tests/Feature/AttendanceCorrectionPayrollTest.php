<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\AttendanceCorrection;
use App\Models\JadwalKerja;
use App\Models\Karyawan;
use App\Models\LokasiKerja;
use App\Models\Penggajian;
use App\Models\Shift;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionPayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_correction_regenerates_unpaid_payroll(): void
    {
        [$admin, $attendance, $correction] = $this->scenario();
        app(PayrollService::class)->generate(7, 2026);
        $this->assertDatabaseHas('penggajian', ['karyawan_id' => $attendance->karyawan_id, 'total_tidak_lengkap' => 1, 'total_gaji' => 0]);

        $this->withSession(['admin_logged_in' => true, 'admin_user' => ['id' => $admin->id]])
            ->post(route('admin.corrections.approve', $correction))->assertRedirect();

        $this->assertDatabaseHas('attendance_corrections', ['id' => $correction->id, 'status' => 'approved']);
        $this->assertDatabaseHas('penggajian', ['karyawan_id' => $attendance->karyawan_id, 'total_hadir' => 1, 'total_tidak_lengkap' => 0, 'total_paid_minutes' => 480, 'total_gaji' => 80000]);
    }

    public function test_paid_payroll_blocks_correction_approval(): void
    {
        [$admin, $attendance, $correction] = $this->scenario();
        app(PayrollService::class)->generate(7, 2026);
        Penggajian::where('karyawan_id', $attendance->karyawan_id)->update(['status_bayar' => 'sudah_dibayar', 'tanggal_bayar' => '2026-07-31']);

        $this->withSession(['admin_logged_in' => true, 'admin_user' => ['id' => $admin->id]])
            ->post(route('admin.corrections.approve', $correction))->assertSessionHas('error');

        $this->assertDatabaseHas('attendance_corrections', ['id' => $correction->id, 'status' => 'pending']);
        $this->assertDatabaseHas('absensi', ['id' => $attendance->id, 'status_kehadiran' => 'tidak_lengkap', 'clock_out_at' => null]);
    }

    private function scenario(): array
    {
        $location = LokasiKerja::create(['nama_lokasi' => 'Test', 'latitude' => -6.2, 'longitude' => 106.8, 'radius_meter' => 100, 'is_aktif' => true]);
        $admin = User::create(['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@test.local', 'password' => 'password', 'role' => 'admin', 'status' => 'aktif']);
        $user = User::create(['name' => 'Pegawai', 'username' => 'pegawai', 'email' => 'pegawai@test.local', 'password' => 'password', 'role' => 'karyawan', 'status' => 'aktif']);
        $employee = Karyawan::create(['user_id' => $user->id, 'nama_lengkap' => 'Pegawai', 'jabatan' => 'Barista', 'lokasi_kerja_id' => $location->id, 'tarif_per_jam' => 10000, 'tgl_bergabung' => '2026-01-01', 'status' => 'aktif']);
        $shift = Shift::create(['nama' => 'Pagi', 'jam_mulai' => '08:00', 'jam_selesai' => '16:00', 'toleransi_menit' => 15, 'is_aktif' => true]);
        $schedule = JadwalKerja::create(['karyawan_id' => $employee->id, 'shift_id' => $shift->id, 'lokasi_kerja_id' => $location->id, 'tanggal_operasional' => '2026-07-20']);
        $attendance = Absensi::create(['karyawan_id' => $employee->id, 'jadwal_kerja_id' => $schedule->id, 'tanggal' => '2026-07-20', 'clock_in_at' => '2026-07-20 08:00:00', 'jam_masuk' => '08:00', 'status_kehadiran' => 'tidak_lengkap']);
        $correction = AttendanceCorrection::create(['absensi_id' => $attendance->id, 'karyawan_id' => $employee->id, 'requested_clock_out_at' => '2026-07-20 16:00:00', 'alasan' => 'Lupa', 'status' => 'pending']);

        return [$admin, $attendance, $correction];
    }
}
