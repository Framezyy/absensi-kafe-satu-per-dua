<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaceEmbedding extends Model
{
    protected $table = 'face_embeddings';

    protected $fillable = ['karyawan_id', 'embedding_vector', 'foto_referensi_path', 'tgl_registrasi', 'is_aktif'];

    protected function casts(): array
    {
        return ['embedding_vector' => 'array', 'tgl_registrasi' => 'date', 'is_aktif' => 'boolean'];
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
