<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Absensi;

class AuditController extends Controller {
    public function index() {
        $logs = Absensi::with('karyawan')
            ->whereNotNull('jam_masuk')
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_masuk', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($a) {
                return (object)[
                    'nama' => $a->karyawan->nama_lengkap,
                    'aksi' => $a->jam_pulang ? 'Absen Masuk + Pulang' : 'Absen Masuk',
                    'waktu' => $a->tanggal->format('Y-m-d') . ' ' . $a->jam_masuk,
                    'similarity' => $a->face_similarity_score,
                    'challenge' => 'Auto',
                    'foto_path' => $a->face_image_path ?? '-',
                    'status' => $a->face_verified ? 'Match' : 'N/A',
                ];
            });

        return view('admin.audit.index', compact('logs'));
    }
}