<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['nama', 'jam_mulai', 'jam_selesai', 'toleransi_menit', 'is_aktif'];

    protected function casts(): array
    {
        return ['is_aktif' => 'boolean'];
    }

    public function jadwalKerja()
    {
        return $this->hasMany(JadwalKerja::class);
    }
}
