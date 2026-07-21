<?php

use App\Models\Absensi;
use App\Services\AttendanceCalculationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:mark-incomplete', function (AttendanceCalculationService $calculation) {
    $count = 0;
    $skipped = 0;
    Absensi::with('jadwalKerja.shift')->whereNotNull('clock_in_at')->whereNull('clock_out_at')->where('status_kehadiran', 'berjalan')->chunkById(100, function ($records) use (&$count, &$skipped, $calculation) {
        foreach ($records as $record) {
            $schedule = $record->jadwalKerja;
            if (! $schedule?->shift) {
                $skipped++;

                continue;
            }
            [, $end] = $calculation->shiftPeriod($schedule->tanggal_operasional, $schedule->shift->jam_mulai, $schedule->shift->jam_selesai);
            if (now()->greaterThanOrEqualTo($end->addMinutes((int) config('attendance.incomplete_grace_minutes', 120)))) {
                $record->update(['status_kehadiran' => 'tidak_lengkap', 'worked_minutes' => 0, 'paid_minutes' => 0]);
                $count++;
            }
        }
    });
    $this->info("{$count} sesi ditandai tidak_lengkap.");
    if ($skipped > 0) {
        $this->warn("{$skipped} sesi dilewati karena jadwal atau shift tidak tersedia.");
    }
})->purpose('Mark overdue open attendance sessions incomplete')->everyFifteenMinutes()->withoutOverlapping();
