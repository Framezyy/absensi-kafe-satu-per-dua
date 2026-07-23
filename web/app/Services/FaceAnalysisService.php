<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\FaceEmbedding;
use App\Models\Karyawan;
use Illuminate\Support\Collection;

class FaceAnalysisService
{
    public function summary(): array
    {
        $activeEmployees = Karyawan::where('status', 'aktif')->count();
        $embeddings = FaceEmbedding::with('karyawan')->get();
        $validActiveEmbeddings = $embeddings->filter(fn (FaceEmbedding $embedding) => $embedding->is_aktif && $this->embeddingQuality($embedding->embedding_vector) === 'Valid 512D');
        $scores = Absensi::whereNotNull('face_similarity_score')->pluck('face_similarity_score')->map(fn ($score) => (float) $score);
        $verifyThreshold = FaceRecognitionService::VERIFY_THRESHOLD;
        $duplicateThreshold = FaceRecognitionService::DUPLICATE_THRESHOLD;

        return [
            'active_employees' => $activeEmployees,
            'valid_enrollments' => $validActiveEmbeddings->count(),
            'not_enrolled' => max(0, $activeEmployees - $validActiveEmbeddings->pluck('karyawan_id')->unique()->count()),
            'coverage_percentage' => $activeEmployees > 0 ? round($validActiveEmbeddings->pluck('karyawan_id')->unique()->count() / $activeEmployees * 100, 2) : 0.0,
            'sample_count' => $scores->count(),
            'minimum_score' => $scores->min(),
            'average_score' => $scores->isNotEmpty() ? round($scores->average(), 4) : null,
            'maximum_score' => $scores->max(),
            'accepted_count' => $scores->filter(fn (float $score) => $score >= $verifyThreshold)->count(),
            'near_threshold_count' => $scores->filter(fn (float $score) => $score >= $verifyThreshold && $score <= $verifyThreshold + 0.05)->count(),
            'verify_threshold' => $verifyThreshold,
            'duplicate_threshold' => $duplicateThreshold,
            'latest_score' => $scores->last(),
            'embeddings' => $embeddings->map(fn (FaceEmbedding $embedding) => (object) [
                'karyawan' => $embedding->karyawan,
                'is_aktif' => $embedding->is_aktif,
                'dimension' => is_array($embedding->embedding_vector) ? count($embedding->embedding_vector) : 0,
                'quality' => $this->embeddingQuality($embedding->embedding_vector),
                'registered_at' => $embedding->tgl_registrasi,
                'has_reference_photo' => filled($embedding->foto_referensi_path),
            ]),
        ];
    }

    public function verificationRows(int $limit = 50): Collection
    {
        return Absensi::with('karyawan', 'jadwalKerja.shift')
            ->whereNotNull('face_similarity_score')
            ->latest('clock_in_at')
            ->limit($limit)
            ->get()
            ->map(function (Absensi $attendance) {
                $score = (float) $attendance->face_similarity_score;
                $threshold = FaceRecognitionService::VERIFY_THRESHOLD;

                return (object) [
                    'attendance' => $attendance,
                    'score' => $score,
                    'margin' => round($score - $threshold, 4),
                    'decision' => $score >= $threshold ? 'Match' : 'Di bawah threshold',
                    'is_consistent' => $attendance->face_verified && $score >= $threshold,
                ];
            });
    }

    public function embeddingQuality(mixed $vector): string
    {
        if (! is_array($vector) || count($vector) === 0) {
            return 'Tidak tersedia';
        }
        if (collect($vector)->contains(fn ($value) => ! is_numeric($value) || ! is_finite((float) $value))) {
            return 'Komponen tidak valid';
        }
        $normSquared = array_reduce($vector, fn (float $sum, $value) => $sum + ((float) $value ** 2), 0.0);
        if ($normSquared == 0.0 || count(array_unique(array_map(fn ($value) => round((float) $value, 6), $vector))) === 1) {
            return 'Vektor tidak valid';
        }

        return match (count($vector)) {
            512 => 'Valid 512D',
            128 => 'Legacy 128D',
            default => 'Dimensi '.count($vector).'D',
        };
    }
}
