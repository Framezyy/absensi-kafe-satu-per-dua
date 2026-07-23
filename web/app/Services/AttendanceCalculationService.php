<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Container\Container;

class AttendanceCalculationService
{
    public function lateMinutes(CarbonInterface $clockIn, CarbonInterface $scheduledStart, int $toleranceMinutes = 0): int
    {
        return (int) max(0, $scheduledStart->copy()->addMinutes($toleranceMinutes)->diffInMinutes($clockIn, false));
    }

    public function earlyLeaveMinutes(?CarbonInterface $clockOut, CarbonInterface $scheduledEnd): int
    {
        if (! $clockOut || $clockOut->greaterThanOrEqualTo($scheduledEnd)) {
            return 0;
        }

        return (int) $clockOut->diffInMinutes($scheduledEnd);
    }

    public function shiftPeriod(string|CarbonInterface $operationalDate, string $startTime, string $endTime, ?string $timezone = null): array
    {
        $timezone ??= config('app.timezone');
        $date = $operationalDate instanceof CarbonInterface ? $operationalDate->format('Y-m-d') : $operationalDate;
        $start = CarbonImmutable::parse($date.' '.$startTime, $timezone);
        $end = CarbonImmutable::parse($date.' '.$endTime, $timezone);

        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return [$start, $end];
    }

    public function calculate(CarbonInterface $clockIn, CarbonInterface $clockOut, CarbonInterface $scheduledStart, CarbonInterface $scheduledEnd, int $toleranceMinutes = 0): array
    {
        $workedMinutes = (int) max(0, $clockIn->diffInMinutes($clockOut, false));
        $lateMinutes = $this->lateMinutes($clockIn, $scheduledStart, $toleranceMinutes);
        $overlapStart = $clockIn->greaterThan($scheduledStart) ? $clockIn : $scheduledStart;
        $overlapEnd = $clockOut->lessThan($scheduledEnd) ? $clockOut : $scheduledEnd;
        $paidMinutes = (int) min($this->maxPaidMinutes(), max(0, $overlapStart->diffInMinutes($overlapEnd, false)));

        return [
            'late_minutes' => $lateMinutes,
            'worked_minutes' => $workedMinutes,
            'paid_minutes' => $paidMinutes,
        ];
    }

    public function salary(int $paidMinutes, int|float|string $hourlyRate): int
    {
        return (int) round(min($this->maxPaidMinutes(), max(0, $paidMinutes)) * (float) $hourlyRate / 60);
    }

    private function maxPaidMinutes(): int
    {
        $container = Container::getInstance();

        return $container?->bound('config')
            ? (int) $container->make('config')->get('payroll.max_paid_minutes_per_shift', 480)
            : 480;
    }
}
