<?php

namespace Tests\Unit;

use App\Services\FaceAnalysisService;
use App\Services\FaceRecognitionService;
use PHPUnit\Framework\TestCase;

class FaceAnalysisServiceTest extends TestCase
{
    public function test_cosine_similarity_matches_expected_vectors(): void
    {
        $this->assertEqualsWithDelta(1.0, FaceRecognitionService::cosineSimilarity([1, 0], [1, 0]), 0.000001);
        $this->assertEqualsWithDelta(0.0, FaceRecognitionService::cosineSimilarity([1, 0], [0, 1]), 0.000001);
        $this->assertSame(0.0, FaceRecognitionService::cosineSimilarity([1], [1, 2]));
    }

    public function test_embedding_quality_handles_valid_legacy_and_invalid_vectors(): void
    {
        $analysis = new FaceAnalysisService;

        $this->assertSame('Valid 512D', $analysis->embeddingQuality($this->vector(512)));
        $this->assertSame('Legacy 128D', $analysis->embeddingQuality($this->vector(128)));
        $this->assertSame('Vektor tidak valid', $analysis->embeddingQuality(array_fill(0, 512, 0.1)));
        $this->assertSame('Tidak tersedia', $analysis->embeddingQuality(null));
    }

    private function vector(int $dimension): array
    {
        return array_map(fn (int $index) => ($index + 1) / $dimension, range(0, $dimension - 1));
    }
}
