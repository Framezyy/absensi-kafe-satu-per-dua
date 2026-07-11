<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FaceEmbedding;
use App\Services\FaceRecognitionService;

$svc = new FaceRecognitionService();
$andi = FaceEmbedding::where('karyawan_id', 1)->first();
$dewi = FaceEmbedding::where('karyawan_id', 4)->first();

if (!$andi || !$dewi) {
    echo "SKIP: embedding tidak lengkap (andi=" . ($andi?"ada":"null") . ", dewi=" . ($dewi?"ada":"null") . ")\n";
    exit;
}

$simSame = FaceRecognitionService::cosineSimilarity($andi->embedding_vector, $andi->embedding_vector);
echo "TEST 1 - Andi vs Andi: " . round($simSame, 4) . " (harus ~1.0)\n";

$simDiff = FaceRecognitionService::cosineSimilarity($andi->embedding_vector, $dewi->embedding_vector);
echo "TEST 2 - Andi vs Dewi: " . round($simDiff, 4) . " (harus < 0.7)\n";

$others = FaceEmbedding::where('is_aktif', true)->with('karyawan')->get();
$dup = $svc->findDuplicateFace($andi->embedding_vector, $others);
echo "TEST 3 - Daftar wajah Andi (found harus true): found=" . ($dup['found'] ? 'true' : 'false');
if ($dup['found']) echo ", cocok: " . $dup['nama'] . " (sim=" . $dup['similarity'] . ")";
echo "\n";

$random = [];
mt_srand(42);
for ($i = 0; $i < 512; $i++) { $random[] = (mt_rand(-1000, 1000) / 1000.0); }
$dup2 = $svc->findDuplicateFace($random, $others);
echo "TEST 4 - Wajah acak (found harus false): found=" . ($dup2['found'] ? 'true' : 'false') . " (sim tertinggi=" . $dup2['similarity'] . ")\n";