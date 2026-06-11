<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model {
    protected $table = 'absensi';
    protected $fillable = ['karyawan_id', 'tanggal', 'jam_masuk', 'jam_pulang', 'lat_masuk', 'lng_masuk', 'lat_pulang', 'lng_pulang', 'status_kehadiran', 'face_verified', 'face_similarity_score', 'face_image_path', 'keterangan', 'is_synced'];
    protected function casts(): array {
        return ['tanggal' => 'date', 'face_verified' => 'boolean', 'face_similarity_score' => 'decimal:4', 'is_synced' => 'boolean'];
    }
    public function karyawan() { return $this->belongsTo(Karyawan::class); }
}