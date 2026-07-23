<?php

namespace Tests\Feature;

use App\Models\Izin;
use App\Models\JadwalKerja;
use App\Models\Karyawan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_pages_smoke_as_valid_admin(): void
    {
        $admin = $this->createAdmin();
        [, $employee, $location] = $this->createEmployee();
        $shift = $this->createShift();
        $schedule = JadwalKerja::where('karyawan_id', $employee->id)->whereDate('tanggal_operasional', today())->first();

        foreach ([
            route('admin.dashboard'), route('admin.karyawan.index'), route('admin.karyawan.create'), route('admin.karyawan.edit', $employee),
            route('admin.lokasi.index'), route('admin.monitor.index'), route('admin.face-analysis.index'), route('admin.izin.index'),
            route('admin.shifts.index'), route('admin.jadwal.index'), route('admin.jadwal.edit', $employee),
            route('admin.corrections.index'), route('admin.payroll.index'),
        ] as $url) {
            $this->withSession($this->adminSession($admin))->get($url)->assertOk();
        }
    }

    public function test_shift_master_is_read_only_and_only_contains_morning_and_night(): void
    {
        $admin = $this->createAdmin();
        $this->createShift(['nama' => 'Pagi', 'jam_mulai' => '08:00', 'jam_selesai' => '16:00']);
        $this->createShift(['nama' => 'Malam', 'jam_mulai' => '16:00', 'jam_selesai' => '00:00']);

        $this->withSession($this->adminSession($admin))->get(route('admin.shifts.index'))->assertOk()->assertDontSee('Tambah Shift');
        $this->withSession($this->adminSession($admin))->get('/admin/shifts/create')->assertNotFound();
        $this->withSession($this->adminSession($admin))->post('/admin/shifts', [])->assertMethodNotAllowed();
    }

    public function test_schedule_page_lists_every_employee_and_can_edit_default_shift(): void
    {
        $admin = $this->createAdmin();
        [, $employee, $location] = $this->createEmployee();
        $night = $this->createShift(['nama' => 'Malam', 'jam_mulai' => '16:00', 'jam_selesai' => '00:00']);
        $this->withSession($this->adminSession($admin))->get(route('admin.jadwal.index'))->assertOk()->assertSee($employee->nama_lengkap)->assertDontSee('Tambah Jadwal');
        $this->withSession($this->adminSession($admin))->put(route('admin.jadwal.update', $employee), ['default_shift_id' => $night->id])->assertRedirect(route('admin.jadwal.index'));
        $this->assertDatabaseHas('karyawan', ['id' => $employee->id, 'default_shift_id' => $night->id]);
        $this->assertDatabaseHas('jadwal_kerja', ['karyawan_id' => $employee->id, 'shift_id' => $night->id, 'lokasi_kerja_id' => $location->id]);
        $this->withSession($this->adminSession($admin))->get('/admin/jadwal/create')->assertNotFound();
        $this->withSession($this->adminSession($admin))->post('/admin/jadwal')->assertMethodNotAllowed();
    }

    public function test_location_employee_and_leave_core_flows(): void
    {
        $admin = $this->createAdmin();
        [, $employee, $location] = $this->createEmployee();
        $session = $this->adminSession($admin);
        $this->withSession($session)->put(route('admin.lokasi.update', $location), ['nama_lokasi' => 'Baru', 'latitude' => 91, 'longitude' => 0, 'radius_meter' => 100])->assertSessionHasErrors('latitude');
        $this->withSession($session)->put(route('admin.lokasi.update', $location), ['nama_lokasi' => 'Baru', 'latitude' => -6.3, 'longitude' => 106.9, 'radius_meter' => 150])->assertRedirect(route('admin.lokasi.index'));

        $this->withSession($session)->post(route('admin.karyawan.store'), ['nama' => 'Pegawai Baru', 'jabatan' => 'Kasir', 'username' => 'PEGAWAI.BARU', 'password' => '123456789012', 'tanggal_bergabung' => '2026-01-01', 'lokasi_kerja_id' => $location->id, 'default_shift_id' => $employee->default_shift_id])->assertRedirect(route('admin.karyawan.index'));
        $created = Karyawan::where('nama_lengkap', 'Pegawai Baru')->firstOrFail();
        $this->withSession($session)->put(route('admin.karyawan.update', $created), ['nama' => 'Pegawai Update', 'jabatan' => 'Kasir', 'username' => 'pegawai.baru', 'status' => 'nonaktif', 'lokasi_kerja_id' => $location->id, 'default_shift_id' => $employee->default_shift_id])->assertRedirect(route('admin.karyawan.index'));
        $this->withSession($session)->delete(route('admin.karyawan.destroy', $created))->assertRedirect(route('admin.karyawan.index'));

        $leave = Izin::create(['karyawan_id' => $employee->id, 'tanggal_mulai' => '2026-07-20', 'tanggal_selesai' => '2026-07-20', 'alasan' => 'Sakit', 'status' => 'pending']);
        $this->withSession($session)->post(route('admin.izin.approve', $leave))->assertRedirect(route('admin.izin.index'));
        $this->assertDatabaseHas('izin', ['id' => $leave->id, 'status' => 'approved', 'diproses_oleh' => $admin->id]);
    }
}
