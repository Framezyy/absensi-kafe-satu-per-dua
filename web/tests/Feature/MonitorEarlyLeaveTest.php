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

class MonitorEarlyLeaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_marks_completed_attendance_as_early_leave(): void
    {
        $this->travelTo('2026-07-20 17:00:00');
        $location = LokasiKerja::create(['nama_lokasi' => 'Kafe', 'latitude' => -6.2, 'longitude' => 106.8, 'radius_meter' => 100, 'is_aktif' => true]);
        $user = User::create(['name' => 'Pegawai', 'username' => 'pegawai', 'email' => 'pegawai@test.local', 'password' => 'password', 'role' => 'karyawan', 'status' => 'aktif']);
        $employee = Karyawan::create(['user_id' => $user->id, 'nama_lengkap' => 'Pegawai Pulang Awal', 'jabatan' => 'Barista', 'lokasi_kerja_id' => $location->id, 'tarif_per_jam' => 10000, 'tgl_bergabung' => '2026-01-01', 'status' => 'aktif']);
        $shift = Shift::create(['nama' => 'Pagi', 'jam_mulai' => '08:00', 'jam_selesai' => '16:00', 'toleransi_menit' => 15, 'is_aktif' => true]);
        $schedule = JadwalKerja::create(['karyawan_id' => $employee->id, 'shift_id' => $shift->id, 'lokasi_kerja_id' => $location->id, 'tanggal_operasional' => '2026-07-20']);
        Absensi::create(['karyawan_id' => $employee->id, 'jadwal_kerja_id' => $schedule->id, 'tanggal' => '2026-07-20', 'clock_in_at' => '2026-07-20 08:00:00', 'clock_out_at' => '2026-07-20 14:30:00', 'jam_masuk' => '08:00', 'jam_pulang' => '14:30', 'status_kehadiran' => 'selesai', 'worked_minutes' => 390, 'paid_minutes' => 390]);

        $admin = $this->createAdmin();
        $response = $this->withSession($this->adminSession($admin))->get(route('admin.monitor.index'));

        $response->assertOk()
            ->assertSee('Pegawai Pulang Awal')
            ->assertSee('Pulang Lebih Awal')
            ->assertDontSee('-90 menit dari jadwal')
            ->assertDontSee('menit lebih awal = max(0, jam selesai shift - jam pulang)')
            ->assertDontSee('Rumus keterlambatan');
    }
}
