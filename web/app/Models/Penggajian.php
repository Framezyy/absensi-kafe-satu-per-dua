<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Penggajian extends Model {
    protected $table = 'penggajian';
    protected $fillable = ['karyawan_id', 'periode_bulan', 'periode_tahun', 'total_hadir', 'tarif_harian', 'total_honorarium', 'total_bonus', 'total_gaji', 'status_bayar', 'tanggal_bayar'];
    protected function casts(): array {
        return ['tarif_harian' => 'decimal:2', 'total_honorarium' => 'decimal:2', 'total_bonus' => 'decimal:2', 'total_gaji' => 'decimal:2', 'tanggal_bayar' => 'date'];
    }
    public function karyawan() { return $this->belongsTo(Karyawan::class); }
}