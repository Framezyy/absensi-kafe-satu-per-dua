<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Penggajian;
use App\Services\PayrollService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollImmutableTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_period_regeneration_is_rejected_without_mutating_snapshot(): void
    {
        [, $employee] = $this->createEmployee();
        Absensi::create(['karyawan_id' => $employee->id, 'tanggal' => '2026-07-01', 'clock_in_at' => '2026-07-01 08:00', 'clock_out_at' => '2026-07-01 16:00', 'jam_masuk' => '08:00', 'jam_pulang' => '16:00', 'status_kehadiran' => 'selesai', 'paid_minutes' => 480]);
        app(PayrollService::class)->generate(7, 2026);
        $snapshot = Penggajian::firstOrFail();
        $snapshot->update(['status_bayar' => 'sudah_dibayar', 'tanggal_bayar' => '2026-07-31']);
        Absensi::query()->update(['paid_minutes' => 60]);

        try {
            app(PayrollService::class)->generate(7, 2026);
            $this->fail('Expected paid payroll regeneration to fail.');
        } catch (DomainException) {
            $this->assertDatabaseHas('penggajian', ['id' => $snapshot->id, 'total_paid_minutes' => 480, 'total_gaji' => 80000, 'status_bayar' => 'sudah_dibayar']);
        }
    }

    public function test_payroll_pages_generate_validation_and_paid_error(): void
    {
        $admin = $this->createAdmin();
        [, $employee] = $this->createEmployee();
        $payroll = Penggajian::create(['karyawan_id' => $employee->id, 'periode_bulan' => 7, 'periode_tahun' => 2026, 'total_hadir' => 1, 'tarif_per_jam' => 10000, 'total_paid_minutes' => 480, 'total_tidak_lengkap' => 0, 'total_gaji' => 80000, 'status_bayar' => 'sudah_dibayar']);
        $session = $this->adminSession($admin);

        $this->withSession($session)->get(route('admin.payroll.index', ['period' => 'invalid']))->assertSessionHasErrors('period');
        $this->withSession($session)->get(route('admin.payroll.show', $payroll))->assertOk();
        $this->withSession($session)->post(route('admin.payroll.generate'), [])->assertSessionHasErrors('period');
        $this->withSession($session)->post(route('admin.payroll.generate'), ['period' => '2026-07'])->assertSessionHas('error');
    }
}
