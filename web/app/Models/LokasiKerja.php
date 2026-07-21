<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LokasiKerja extends Model
{
    protected $table = 'lokasi_kerja';

    protected $fillable = ['nama_lokasi', 'latitude', 'longitude', 'radius_meter', 'is_aktif'];

    protected function casts(): array
    {
        return ['latitude' => 'decimal:8', 'longitude' => 'decimal:8', 'is_aktif' => 'boolean'];
    }

    public function karyawan()
    {
        return $this->hasMany(Karyawan::class);
    }

    public function jadwalKerja()
    {
        return $this->hasMany(JadwalKerja::class, 'lokasi_kerja_id');
    }
}
