<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\FaceEmbedding;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;

class FaceController extends Controller {
    private FaceRecognitionService $faceService;

    public function __construct(FaceRecognitionService $faceService) {
        $this->faceService = $faceService;
    }

    public function enroll(Request $request) {
        $request->validate([
            'frames' => 'required|array|min:1',
            'frames.*' => 'required|file|max:2048',
        ]);

        $karyawan = $request->user()->karyawan;
        $files = $request->file('frames');

        if (!is_array($files)) {
            $files = [$files];
        }

        // Kirim ke FastAPI untuk generate mean embedding.
        $result = $this->faceService->enrollFromFrames($files);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 422);
        }

        // Simpan embedding ke DB.
        $fotoPath = $files[0]->store('face_enroll', 'public');

        FaceEmbedding::updateOrCreate(
            ['karyawan_id' => $karyawan->id],
            [
                'embedding_vector' => $result['embedding'],
                'foto_referensi_path' => $fotoPath,
                'tgl_registrasi' => now(),
                'is_aktif' => true,
            ]
        );

        return response()->json([
            'message' => 'Wajah berhasil didaftarkan.',
            'frames_used' => $result['frames_used'],
        ]);
    }

    public function verify(Request $request) {
        $request->validate([
            'frame' => 'required|file|max:2048',
        ]);

        $karyawan = $request->user()->karyawan;
        $embedding = FaceEmbedding::where('karyawan_id', $karyawan->id)
            ->where('is_aktif', true)
            ->first();

        if (!$embedding) {
            return response()->json(['message' => 'Data wajah tidak ditemukan.', 'match' => false], 404);
        }

        $result = $this->faceService->verify(
            $request->file('frame'),
            $embedding->embedding_vector
        );

        return response()->json($result);
    }
}