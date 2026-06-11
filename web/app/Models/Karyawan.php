<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model {
    protected $table = 'karyawan';
    protected $fillable = ['user_id', 'nik', 'nama_lengkap', 'jabatan', 'lokasi_kerja_id', 'tarif_gaji_harian', 'tgl_bergabung', 'status'];
    protected function casts(): array {
        return ['tarif_gaji_harian' => 'decimal:2', 'tgl_bergabung' => 'date'];
    }
    public function user() { return $this->belongsTo(User::class); }
    public function lokasiKerja() { return $this->belongsTo(LokasiKerja::class, 'lokasi_kerja_id'); }
    public function absensi() { return $this->hasMany(Absensi::class); }
    public function penggajian() { return $this->hasMany(Penggajian::class); }
    public function bonus() { return $this->hasMany(Bonus::class); }
    public function izin() { return $this->hasMany(Izin::class); }
    public function faceEmbedding() { return $this->hasOne(FaceEmbedding::class); }
}