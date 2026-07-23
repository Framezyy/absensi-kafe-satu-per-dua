<?php

namespace Tests\Feature;

use App\Models\FaceEmbedding;
use App\Models\JadwalKerja;
use App\Services\FaceRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class FaceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_enroll_requires_images_and_stores_valid_embedding_and_photo(): void
    {
        Storage::fake('public');
        [$user, $employee] = $this->createEmployee();
        Sanctum::actingAs($user);
        $embedding = $this->embedding();
        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) use ($embedding) {
            $mock->shouldReceive('enrollFromFrames')->once()->andReturn(['success' => true, 'embedding' => $embedding, 'frames_used' => 1]);
            $mock->shouldReceive('findDuplicateFace')->never();
        });

        $this->postJson('/api/face/enroll', ['frames' => [UploadedFile::fake()->create('bad.txt', 1, 'text/plain')]])->assertUnprocessable();
        $this->postJson('/api/face/enroll', ['frames' => [UploadedFile::fake()->image('face.jpg')]])->assertOk()->assertJsonPath('frames_used', 1);

        $stored = FaceEmbedding::where('karyawan_id', $employee->id)->firstOrFail();
        Storage::disk('public')->assertExists($stored->foto_referensi_path);
    }

    public function test_verify_handles_missing_embedding_mismatch_and_unavailable_service(): void
    {
        [$user, $employee] = $this->createEmployee();
        Sanctum::actingAs($user);
        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verify')->twice()->andReturn(
                ['service_available' => true, 'match' => false, 'similarity' => 0.4],
                ['service_available' => false, 'match' => false, 'similarity' => 0],
            );
        });
        $payload = ['frame' => UploadedFile::fake()->image('face.jpg'), 'action' => 'clock_in'];
        $this->postJson('/api/face/verify', $payload)->assertNotFound()->assertJsonPath('code', 'FACE_NOT_ENROLLED');

        FaceEmbedding::create(['karyawan_id' => $employee->id, 'embedding_vector' => $this->embedding(), 'is_aktif' => true, 'tgl_registrasi' => now()]);
        $this->postJson('/api/face/verify', ['frame' => UploadedFile::fake()->image('face.jpg'), 'action' => 'clock_in'])->assertUnprocessable()->assertJsonPath('code', 'FACE_MISMATCH');

        $this->postJson('/api/face/verify', ['frame' => UploadedFile::fake()->image('face.jpg'), 'action' => 'clock_in'])->assertStatus(503)->assertJsonPath('code', 'FACE_SERVICE_UNAVAILABLE');
    }

    public function test_laravel_enforces_similarity_threshold_even_when_service_says_match(): void
    {
        [$user, $employee, $location] = $this->createEmployee();
        Sanctum::actingAs($user);
        FaceEmbedding::create(['karyawan_id' => $employee->id, 'embedding_vector' => $this->embedding(), 'is_aktif' => true, 'tgl_registrasi' => now()]);
        $shift = $this->createShift();
        JadwalKerja::create(['karyawan_id' => $employee->id, 'shift_id' => $shift->id, 'lokasi_kerja_id' => $location->id, 'tanggal_operasional' => '2026-07-20']);
        $this->travelTo('2026-07-20 08:00');
        $this->mock(FaceRecognitionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verify')->once()->andReturn(['service_available' => true, 'match' => true, 'similarity' => 0.69, 'threshold' => 0.5]);
        });

        $this->postJson('/api/face/verify', ['frame' => UploadedFile::fake()->image('face.jpg'), 'action' => 'clock_in'])
            ->assertUnprocessable()->assertJsonPath('code', 'FACE_MISMATCH')->assertJsonPath('match', false);
    }

    private function embedding(): array
    {
        return array_map(fn (int $value) => $value / 128, range(1, 128));
    }
}
