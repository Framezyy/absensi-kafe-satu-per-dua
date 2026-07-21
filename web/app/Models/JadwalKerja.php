<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalKerja extends Model
{
    protected $table = 'jadwal_kerja';

    protected $fillable = ['karyawan_id', 'shift_id', 'lokasi_kerja_id', 'tanggal_operasional'];

    protected function casts(): array
    {
        return ['tanggal_operasional' => 'date'];
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function lokasiKerja()
    {
        return $this->belongsTo(LokasiKerja::class, 'lokasi_kerja_id');
    }

    public function absensi()
    {
        return $this->hasOne(Absensi::class);
    }
}
