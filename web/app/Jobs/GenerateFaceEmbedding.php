<?php

namespace App\Jobs;

use App\Models\FaceEmbedding;
use App\Services\FaceRecognitionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateFaceEmbedding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(private int $faceEmbeddingId) {}

    public function handle(FaceRecognitionService $faceService): void
    {
        $record = FaceEmbedding::find($this->faceEmbeddingId);
        if (! $record || ! $record->foto_referensi_path) {
            Log::warning("GenerateFaceEmbedding: record {$this->faceEmbeddingId} tidak ditemukan.");

            return;
        }

        $fullPath = Storage::disk('public')->path($record->foto_referensi_path);
        if (! file_exists($fullPath)) {
            Log::error("GenerateFaceEmbedding: file tidak ada: {$fullPath}");

            return;
        }

        // Generate embedding via FastAPI.
        $tmpFile = new UploadedFile($fullPath, basename($fullPath), null, null, true);
        $result = $faceService->embed($tmpFile);

        if (! isset($result['success']) || ! $result['success'] || ! isset($result['embedding'])) {
            Log::warning('GenerateFaceEmbedding: FastAPI gagal: '.($result['message'] ?? 'unknown'));
            $this->release(30);

            return;
        }

        $embedding = $result['embedding'];

        // Cek duplikat: bandingkan dengan embedding karyawan lain.
        $others = FaceEmbedding::where('is_aktif', true)
            ->where('id', '!=', $record->id)
            ->whereNotNull('embedding_vector')
            ->with('karyawan')
            ->get()
            ->filter(fn ($e) => is_array($e->embedding_vector) && count($e->embedding_vector) > 100);

        $isDuplicate = false;
        if ($others->isNotEmpty()) {
            $dup = $faceService->findDuplicateFace($embedding, $others);
            if ($dup['found']) {
                $isDuplicate = true;
                Log::warning("DUPLIKAT WAJAH: karyawan_id={$record->karyawan_id} mirip dengan {$dup['nama']} (sim={$dup['similarity']}). Record di-nonaktifkan.");
            }
        }

        if ($isDuplicate) {
            // Nonaktifkan enrollment — admin akan diberitahu.
            $record->update(['embedding_vector' => $embedding, 'is_aktif' => false]);
        } else {
            // Simpan embedding asli.
            $record->update(['embedding_vector' => $embedding]);
            Log::info("GenerateFaceEmbedding: embedding berhasil untuk karyawan_id={$record->karyawan_id}");
        }
    }
}
