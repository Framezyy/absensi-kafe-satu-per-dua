<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\FaceEmbedding;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FaceController extends Controller {
    private FaceRecognitionService $faceService;

    public function __construct(FaceRecognitionService $faceService) {
        $this->faceService = $faceService;
    }

    /**
     * Enrollment wajah — SYNC.
     *
     * Flow:
     * 1. Generate embedding via FastAPI (SYNC, foto kecil ~50KB = cepat)
     * 2. Cek duplikat terhadap SELURUH embedding aktif di DB
     * 3. Jika duplikat → TOLAK 422
     * 4. Jika unik → simpan embedding + foto
     */
    public function enroll(Request $request) {
        $request->validate([
            'frames' => 'required|array|min:1',
            'frames.*' => 'required|file|max:5120',
        ]);

        $karyawan = $request->user()->karyawan;
        $files = $request->file('frames');
        if (!is_array($files)) { $files = [$files]; }

        // STEP 1: Generate embedding via FastAPI.
        $result = $this->faceService->enrollFromFrames($files);
        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        $newEmbedding = $result['embedding'];

        // STEP 2: Cek duplikat — bandingkan dengan SELURUH embedding aktif karyawan LAIN.
        $otherEmbeddings = FaceEmbedding::where('is_aktif', true)
            ->where('karyawan_id', '!=', $karyawan->id)
            ->whereNotNull('embedding_vector')
            ->with('karyawan')
            ->get()
            ->filter(function ($e) {
                return is_array($e->embedding_vector)
                    && count($e->embedding_vector) >= 100
                    && !$this->isDummyEmbedding($e->embedding_vector);
            });

        if ($otherEmbeddings->isNotEmpty()) {
            $dup = $this->faceService->findDuplicateFace($newEmbedding, $otherEmbeddings);
            if ($dup['found']) {
                Log::warning("ENROLLMENT DITOLAK: karyawan_id={$karyawan->id} wajah mirip {$dup['nama']} (sim={$dup['similarity']})");
                return response()->json([
                    'message' => 'Wajah ini sudah terdaftar pada akun karyawan lain ('
                        . $dup['nama'] . '). Satu wajah hanya boleh untuk satu akun.',
                    'duplicate' => true,
                ], 422);
            }
        }

        // STEP 3: Simpan embedding + foto.
        $fotoPath = $files[0]->store('face_enroll', 'public');
        FaceEmbedding::updateOrCreate(
            ['karyawan_id' => $karyawan->id],
            [
                'embedding_vector' => $newEmbedding,
                'foto_referensi_path' => $fotoPath,
                'tgl_registrasi' => now(),
                'is_aktif' => true,
            ]
        );

        return response()->json([
            'message' => 'Wajah berhasil didaftarkan.',
            'frames_used' => $result['frames_used'] ?? 1,
        ]);
    }

    /**
     * Verifikasi wajah saat absensi.
     */
    public function verify(Request $request) {
        $request->validate([
            'frame' => 'required|file|max:5120',
        ]);

        $karyawan = $request->user()->karyawan;
        $embedding = FaceEmbedding::where('karyawan_id', $karyawan->id)
            ->where('is_aktif', true)
            ->first();

        if (!$embedding) {
            return response()->json([
                'message' => 'Data wajah tidak ditemukan. Silakan daftarkan wajah terlebih dahulu.',
                'match' => false,
                'similarity' => 0,
            ], 404);
        }

        $storedVector = $embedding->embedding_vector;

        // Embedding harus valid (bukan kosong/dummy).
        if (empty($storedVector) || !is_array($storedVector) || count($storedVector) < 100 || $this->isDummyEmbedding($storedVector)) {
            return response()->json([
                'match' => false,
                'similarity' => 0,
                'threshold' => 0.7,
                'message' => 'Data wajah belum valid. Silakan daftarkan ulang wajah.',
            ]);
        }

        // Kirim ke FastAPI untuk verifikasi cosine similarity.
        $result = $this->faceService->verify($request->file('frame'), $storedVector);
        return response()->json($result);
    }

    private function isDummyEmbedding(array $vector): bool {
        if (empty($vector) || count($vector) < 10) return true;
        $firstVal = $vector[0];
        foreach ($vector as $v) {
            if (abs($v - $firstVal) > 0.001) return false;
        }
        return true;
    }
}