<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\JadwalKerja;
use App\Models\Karyawan;
use App\Models\LokasiKerja;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkIncompleteAttendanceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_running_session_becomes_incomplete_without_assumed_worked_time(): void
    {
        $location = LokasiKerja::create(['nama_lokasi' => 'Test', 'latitude' => -6.2, 'longitude' => 106.8, 'radius_meter' => 100, 'is_aktif' => true]);
        $user = User::create(['name' => 'Pegawai', 'username' => 'pegawai', 'email' => 'pegawai@test.local', 'password' => 'password', 'role' => 'karyawan', 'status' => 'aktif']);
        $employee = Karyawan::create(['user_id' => $user->id, 'nama_lengkap' => 'Pegawai', 'jabatan' => 'Barista', 'lokasi_kerja_id' => $location->id, 'tarif_per_jam' => 10000, 'tgl_bergabung' => '2026-01-01', 'status' => 'aktif']);
        $shift = Shift::create(['nama' => 'Pagi', 'jam_mulai' => '08:00', 'jam_selesai' => '16:00', 'toleransi_menit' => 15, 'is_aktif' => true]);
        $schedule = JadwalKerja::create(['karyawan_id' => $employee->id, 'shift_id' => $shift->id, 'lokasi_kerja_id' => $location->id, 'tanggal_operasional' => '2026-07-20']);
        $attendance = Absensi::create(['karyawan_id' => $employee->id, 'jadwal_kerja_id' => $schedule->id, 'tanggal' => '2026-07-20', 'clock_in_at' => '2026-07-20 08:00:00', 'jam_masuk' => '08:00', 'status_kehadiran' => 'berjalan', 'worked_minutes' => 99]);
        $this->travelTo('2026-07-20 18:00:00');

        $this->artisan('attendance:mark-incomplete')->assertSuccessful();

        $attendance->refresh();
        $this->assertSame('tidak_lengkap', $attendance->status_kehadiran);
        $this->assertSame(0, $attendance->worked_minutes);
        $this->assertSame(0, $attendance->paid_minutes);
        $this->assertNull($attendance->clock_out_at);
    }
}
