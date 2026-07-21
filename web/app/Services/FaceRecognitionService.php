<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaceRecognitionService
{
    private string $baseUrl;

    /**
     * Threshold verifikasi absensi harian (identitas harus yakin cocok).
     */
    public const VERIFY_THRESHOLD = 0.70;

    /**
     * Threshold deteksi duplikat saat enrollment. Sengaja lebih rendah
     * dari VERIFY_THRESHOLD karena wajah orang yang SAMA difoto pada
     * waktu/pencahayaan/sudut berbeda sering hanya menghasilkan
     * similarity 0.6-0.65. Deteksi duplikat butuh sensitivitas lebih
     * tinggi (tangkap lebih banyak calon), sementara orang berbeda
     * umumnya < 0.45 sehingga 0.55 tetap aman dari false positive.
     */
    public const DUPLICATE_THRESHOLD = 0.55;

    public function __construct()
    {
        $this->baseUrl = config('services.fastapi.url', 'http://127.0.0.1:8001');
    }

    public function detect(UploadedFile $file): array
    {
        try {
            $response = Http::timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post("{$this->baseUrl}/detect");

            return $response->json() ?? ['face_detected' => false];
        } catch (\Exception $e) {
            Log::error('FaceRecognition detect error: '.$e->getMessage());

            return ['face_detected' => false, 'error' => $e->getMessage()];
        }
    }

    public function embed(UploadedFile $file): array
    {
        try {
            $response = Http::timeout(60)
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post("{$this->baseUrl}/embed");

            return $response->json() ?? ['success' => false];
        } catch (\Exception $e) {
            Log::error('FaceRecognition embed error: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verifikasi wajah via FastAPI /verify.
     *
     * FIX BUG 2: Laravel Http::attach() + ->post() dengan data tambahan
     * TIDAK mengirim form field dengan benar ke FastAPI Form(...).
     * Solusi: gunakan cURL multipart manual yang benar-benar mengirim
     * file + form field secara bersamaan.
     */
    public function verify(UploadedFile $file, array $storedEmbedding): array
    {
        try {
            $filePath = $file->getRealPath();
            $embeddingJson = json_encode($storedEmbedding);

            // Gunakan cURL langsung untuk multipart yang benar.
            $ch = curl_init("{$this->baseUrl}/verify");
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_POSTFIELDS => [
                    'file' => new \CURLFile($filePath, 'image/jpeg', basename($filePath)),
                    'stored_embedding' => $embeddingJson,
                ],
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error("FaceRecognition verify cURL error: {$error}");

                return ['service_available' => false, 'match' => false, 'similarity' => 0, 'message' => 'Layanan verifikasi wajah tidak dapat dihubungi.'];
            }

            $data = json_decode($response, true);
            if (! $data) {
                Log::error("FaceRecognition verify: response bukan JSON valid. HTTP={$httpCode} body=".substr($response, 0, 200));

                return ['service_available' => false, 'match' => false, 'similarity' => 0, 'message' => 'Respons layanan verifikasi wajah tidak valid.'];
            }

            return ['service_available' => $httpCode >= 200 && $httpCode < 300] + $data;
        } catch (\Exception $e) {
            Log::error('FaceRecognition verify error: '.$e->getMessage());

            return ['service_available' => false, 'match' => false, 'similarity' => 0, 'message' => 'Layanan verifikasi wajah tidak tersedia.'];
        }
    }

    public function enrollFromFrames(array $files): array
    {
        $embeddings = [];

        foreach ($files as $file) {
            $result = $this->embed($file);
            if (isset($result['success']) && $result['success'] && isset($result['embedding'])) {
                $embeddings[] = $result['embedding'];
            }
        }

        if (! empty($embeddings)) {
            $count = count($embeddings);
            $dim = count($embeddings[0]);
            $mean = array_fill(0, $dim, 0.0);

            foreach ($embeddings as $emb) {
                for ($i = 0; $i < $dim; $i++) {
                    $mean[$i] += $emb[$i];
                }
            }
            for ($i = 0; $i < $dim; $i++) {
                $mean[$i] /= $count;
            }

            return ['success' => true, 'embedding' => $mean, 'frames_used' => $count];
        }

        Log::warning('FaceRecognition: wajah tidak terdeteksi di semua frame enrollment.');

        return [
            'success' => false,
            'message' => 'Wajah tidak terdeteksi. Pastikan wajah terlihat jelas, pencahayaan cukup, dan posisi berada di dalam bingkai.',
        ];
    }

    public static function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) === 0 || count($a) !== count($b)) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $n = count($a);
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        if ($normA == 0.0 || $normB == 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    public function findDuplicateFace(array $newEmbedding, $existingEmbeddings, float $threshold = self::DUPLICATE_THRESHOLD): array
    {
        $highestSimilarity = 0.0;
        $matchedKaryawan = null;

        foreach ($existingEmbeddings as $embedding) {
            $stored = $embedding->embedding_vector;
            if (! is_array($stored) || count($stored) !== count($newEmbedding)) {
                continue;
            }

            $similarity = self::cosineSimilarity($newEmbedding, $stored);
            if ($similarity > $highestSimilarity) {
                $highestSimilarity = $similarity;
                $matchedKaryawan = $embedding->karyawan;
            }
        }

        if ($highestSimilarity >= $threshold && $matchedKaryawan !== null) {
            return [
                'found' => true,
                'karyawan_id' => $matchedKaryawan->id,
                'nama' => $matchedKaryawan->nama_lengkap,
                'similarity' => round($highestSimilarity, 4),
            ];
        }

        return ['found' => false, 'similarity' => round($highestSimilarity, 4)];
    }
}
