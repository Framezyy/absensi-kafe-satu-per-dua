<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class FaceRecognitionService {
    private string $baseUrl;

    public function __construct() {
        $this->baseUrl = config('services.fastapi.url', 'http://127.0.0.1:8001');
    }

    /**
     * Deteksi wajah dalam gambar.
     */
    public function detect(UploadedFile $file): array {
        $response = Http::timeout(30)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post("{$this->baseUrl}/detect");

        return $response->json();
    }

    /**
     * Generate face embedding dari gambar wajah.
     */
    public function embed(UploadedFile $file): array {
        $response = Http::timeout(30)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post("{$this->baseUrl}/embed");

        return $response->json();
    }

    /**
     * Verifikasi wajah: bandingkan gambar baru dengan embedding tersimpan.
     */
    public function verify(UploadedFile $file, array $storedEmbedding): array {
        $response = Http::timeout(30)
            ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post("{$this->baseUrl}/verify", [
                'stored_embedding' => json_encode($storedEmbedding),
            ]);

        return $response->json();
    }

    /**
     * Generate embedding dari 3 frame dan hitung mean embedding.
     */
    public function enrollFromFrames(array $files): array {
        $embeddings = [];

        foreach ($files as $file) {
            $result = $this->embed($file);
            if ($result['success'] && isset($result['embedding'])) {
                $embeddings[] = $result['embedding'];
            }
        }

        if (empty($embeddings)) {
            return ['success' => false, 'message' => 'Gagal generate embedding dari semua frame.'];
        }

        // Hitung mean embedding.
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
}