<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penggajian extends Model
{
    protected $table = 'penggajian';

    protected $fillable = ['karyawan_id', 'periode_bulan', 'periode_tahun', 'total_hadir', 'tarif_per_jam', 'total_paid_minutes', 'total_tidak_lengkap', 'total_gaji', 'status_bayar', 'tanggal_bayar'];

    protected function casts(): array
    {
        return ['tarif_per_jam' => 'decimal:2', 'total_gaji' => 'decimal:2', 'tanggal_bayar' => 'date'];
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
