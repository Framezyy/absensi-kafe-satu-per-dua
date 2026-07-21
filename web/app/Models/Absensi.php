<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $table = 'absensi';

    protected $fillable = ['karyawan_id', 'jadwal_kerja_id', 'tanggal', 'jam_masuk', 'jam_pulang', 'clock_in_at', 'clock_out_at', 'late_minutes', 'worked_minutes', 'paid_minutes', 'lat_masuk', 'lng_masuk', 'lat_pulang', 'lng_pulang', 'status_kehadiran', 'face_verified', 'face_similarity_score', 'face_image_path', 'keterangan', 'is_synced', 'is_mocked_masuk', 'is_mocked_pulang'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'clock_in_at' => 'datetime', 'clock_out_at' => 'datetime', 'face_verified' => 'boolean', 'face_similarity_score' => 'decimal:4', 'is_synced' => 'boolean', 'is_mocked_masuk' => 'boolean', 'is_mocked_pulang' => 'boolean'];
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function jadwalKerja()
    {
        return $this->belongsTo(JadwalKerja::class);
    }

    public function corrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
