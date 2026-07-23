<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\FaceEmbedding;
use App\Models\Karyawan;
use App\Models\LokasiKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaceAnalysisPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_face_analysis(): void
    {
        $this->get(route('admin.face-analysis.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_sees_real_face_formula_without_raw_embedding_values(): void
    {
        $location = LokasiKerja::create(['nama_lokasi' => 'Kafe', 'latitude' => -6.2, 'longitude' => 106.8, 'radius_meter' => 100, 'is_aktif' => true]);
        $user = User::create(['name' => 'Pegawai', 'username' => 'pegawai', 'email' => 'pegawai@test.local', 'password' => 'password', 'role' => 'karyawan', 'status' => 'aktif']);
        $employee = Karyawan::create(['user_id' => $user->id, 'nama_lengkap' => 'Pegawai Uji', 'jabatan' => 'Barista', 'lokasi_kerja_id' => $location->id, 'tarif_per_jam' => 10000, 'tgl_bergabung' => '2026-01-01', 'status' => 'aktif']);
        $vector = array_map(fn (int $index) => ($index + 1) / 512, range(0, 511));
        FaceEmbedding::create(['karyawan_id' => $employee->id, 'embedding_vector' => $vector, 'tgl_registrasi' => '2026-07-21', 'is_aktif' => true]);
        Absensi::create(['karyawan_id' => $employee->id, 'tanggal' => '2026-07-21', 'clock_in_at' => '2026-07-21 08:00:00', 'jam_masuk' => '08:00', 'status_kehadiran' => 'berjalan', 'face_verified' => true, 'face_similarity_score' => 0.7000]);

        $admin = $this->createAdmin();
        $response = $this->withSession($this->adminSession($admin))->get(route('admin.face-analysis.index'));

        $response->assertOk()
            ->assertSee('Analisis Pengujian Wajah')
            ->assertSee('Cosine Similarity')
            ->assertSee('0,7000')
            ->assertSee('Pegawai Uji')
            ->assertDontSee((string) $vector[300]);
    }
}
