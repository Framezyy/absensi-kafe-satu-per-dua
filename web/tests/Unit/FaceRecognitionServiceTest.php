<?php

namespace Tests\Unit;

use App\Services\FaceRecognitionService;
use Illuminate\Http\UploadedFile;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FaceRecognitionServiceTest extends TestCase
{
    public function test_enrollment_averages_valid_consistent_embeddings(): void
    {
        $a = array_fill(0, 128, 1.0);
        $b = array_fill(0, 128, 3.0);
        $service = Mockery::mock(FaceRecognitionService::class)->makePartial();
        $service->shouldReceive('embed')->twice()->andReturn(['success' => true, 'embedding' => $a], ['success' => true, 'embedding' => $b]);

        $result = $service->enrollFromFrames([UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')]);
        $this->assertTrue($result['success']);
        $this->assertSame(2.0, $result['embedding'][0]);
    }

    #[DataProvider('invalidEmbeddings')]
    public function test_enrollment_rejects_invalid_embedding_values(array $embedding): void
    {
        $service = Mockery::mock(FaceRecognitionService::class)->makePartial();
        $service->shouldReceive('embed')->once()->andReturn(['success' => true, 'embedding' => $embedding]);
        $this->assertFalse($service->enrollFromFrames([UploadedFile::fake()->image('a.jpg')])['success']);
    }

    public static function invalidEmbeddings(): array
    {
        return [
            'wrong dimensions' => [array_fill(0, 99, 1.0)],
            'non numeric' => [array_merge(array_fill(0, 127, 1.0), ['bad'])],
            'infinite' => [array_merge(array_fill(0, 127, 1.0), [INF])],
            'nan' => [array_merge(array_fill(0, 127, 1.0), [NAN])],
        ];
    }
}
