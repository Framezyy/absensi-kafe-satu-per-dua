<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Izin extends Model {
    protected $table = 'izin';
    protected $fillable = ['karyawan_id', 'tanggal_mulai', 'tanggal_selesai', 'alasan', 'status', 'diproses_oleh'];
    protected function casts(): array {
        return ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];
    }
    public function karyawan() { return $this->belongsTo(Karyawan::class); }
    public function diprosesOleh() { return $this->belongsTo(User::class, 'diproses_oleh'); }
}