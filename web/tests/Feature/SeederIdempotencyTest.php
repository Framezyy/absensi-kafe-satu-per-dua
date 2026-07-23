<?php

namespace Tests\Feature;

use App\Models\Karyawan;
use App\Models\LokasiKerja;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $counts = [User::count(), Karyawan::count(), LokasiKerja::count(), Shift::count()];
        $andi = User::where('username', 'andisaputra')->firstOrFail();
        $andi->update(['password' => 'passwordbaru', 'status' => 'nonaktif']);
        $andi->karyawan->update(['status' => 'nonaktif']);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($counts, [User::count(), Karyawan::count(), LokasiKerja::count(), Shift::count()]);
        $andi->refresh();
        $this->assertTrue(Hash::check('passwordbaru', $andi->password));
        $this->assertSame('nonaktif', $andi->status);
        $this->assertSame('nonaktif', $andi->karyawan->status);
        $this->assertNotNull($andi->karyawan->default_shift_id);
    }
}
