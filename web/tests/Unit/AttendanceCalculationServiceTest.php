<?php

namespace Tests\Unit;

use App\Services\AttendanceCalculationService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class AttendanceCalculationServiceTest extends TestCase
{
    public function test_night_shift_crosses_midnight_and_caps_paid_time(): void
    {
        $service = new AttendanceCalculationService;
        [$start, $end] = $service->shiftPeriod('2026-07-20', '16:00', '00:00', 'Asia/Jakarta');
        $metrics = $service->calculate(CarbonImmutable::parse('2026-07-20 15:00', 'Asia/Jakarta'), CarbonImmutable::parse('2026-07-21 02:00', 'Asia/Jakarta'), $start, $end, 15);
        $this->assertSame('2026-07-21 00:00:00', $end->format('Y-m-d H:i:s'));
        $this->assertSame(660, $metrics['worked_minutes']);
        $this->assertSame(480, $metrics['paid_minutes']);
        $this->assertSame(0, $metrics['late_minutes']);
    }

    public function test_late_overlap_and_salary_are_calculated_without_overtime(): void
    {
        $service = new AttendanceCalculationService;
        [$start, $end] = $service->shiftPeriod('2026-07-20', '08:00', '16:00', 'Asia/Jakarta');
        $metrics = $service->calculate(CarbonImmutable::parse('2026-07-20 08:30', 'Asia/Jakarta'), CarbonImmutable::parse('2026-07-20 17:00', 'Asia/Jakarta'), $start, $end, 15);
        $this->assertSame(15, $metrics['late_minutes']);
        $this->assertSame(510, $metrics['worked_minutes']);
        $this->assertSame(450, $metrics['paid_minutes']);
        $this->assertSame(75000, $service->salary(450, 10000));
    }

    public function test_early_leave_minutes_compare_clock_out_with_shift_end(): void
    {
        $service = new AttendanceCalculationService;
        [, $end] = $service->shiftPeriod('2026-07-20', '08:00', '16:00', 'Asia/Jakarta');

        $this->assertSame(90, $service->earlyLeaveMinutes(CarbonImmutable::parse('2026-07-20 14:30', 'Asia/Jakarta'), $end));
        $this->assertSame(0, $service->earlyLeaveMinutes(CarbonImmutable::parse('2026-07-20 16:00', 'Asia/Jakarta'), $end));
        $this->assertSame(0, $service->earlyLeaveMinutes(CarbonImmutable::parse('2026-07-20 17:00', 'Asia/Jakarta'), $end));
        $this->assertSame(0, $service->earlyLeaveMinutes(null, $end));
    }
}
