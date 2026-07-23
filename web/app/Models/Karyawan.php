<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    protected $table = 'karyawan';

    protected $fillable = ['user_id', 'nama_lengkap', 'jabatan', 'lokasi_kerja_id', 'default_shift_id', 'tarif_per_jam', 'tgl_bergabung', 'status'];

    // / ID Karyawan format KRY-001 (dari primary key, bukan data pribadi).
    public function getKodeKaryawanAttribute(): string
    {
        return 'KRY-'.str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return ['tarif_per_jam' => 'decimal:2', 'tgl_bergabung' => 'date'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lokasiKerja()
    {
        return $this->belongsTo(LokasiKerja::class, 'lokasi_kerja_id');
    }

    public function defaultShift()
    {
        return $this->belongsTo(Shift::class, 'default_shift_id');
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class);
    }

    public function penggajian()
    {
        return $this->hasMany(Penggajian::class);
    }

    public function jadwalKerja()
    {
        return $this->hasMany(JadwalKerja::class);
    }

    public function attendanceCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function izin()
    {
        return $this->hasMany(Izin::class);
    }

    public function faceEmbedding()
    {
        return $this->hasOne(FaceEmbedding::class);
    }
}
