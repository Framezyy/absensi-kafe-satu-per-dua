<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Bonus extends Model {
    protected $table = 'bonus';
    protected $fillable = ['karyawan_id', 'periode_bulan', 'periode_tahun', 'nominal', 'keterangan'];
    protected function casts(): array {
        return ['nominal' => 'decimal:2'];
    }
    public function karyawan() { return $this->belongsTo(Karyawan::class); }
}