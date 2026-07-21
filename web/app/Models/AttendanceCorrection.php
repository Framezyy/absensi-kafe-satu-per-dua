<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    protected $fillable = ['absensi_id', 'karyawan_id', 'requested_clock_out_at', 'alasan', 'status', 'catatan_admin', 'reviewed_by', 'reviewed_at'];

    protected function casts(): array
    {
        return ['requested_clock_out_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function absensi()
    {
        return $this->belongsTo(Absensi::class);
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
