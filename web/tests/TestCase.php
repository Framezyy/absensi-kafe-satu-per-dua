<?php

namespace Tests;

use App\Models\Karyawan;
use App\Models\LokasiKerja;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createLocation(array $attributes = []): LokasiKerja
    {
        return LokasiKerja::create($attributes + ['nama_lokasi' => 'Lokasi Test', 'latitude' => -6.2, 'longitude' => 106.8, 'radius_meter' => 100, 'is_aktif' => true]);
    }

    protected function createEmployee(array $userAttributes = [], array $employeeAttributes = []): array
    {
        $location = $employeeAttributes['location'] ?? $this->createLocation();
        unset($employeeAttributes['location']);
        $shift = $employeeAttributes['shift'] ?? Shift::firstOrCreate(
            ['nama' => 'Pagi'],
            ['jam_mulai' => '08:00', 'jam_selesai' => '16:00', 'toleransi_menit' => 15, 'is_aktif' => true],
        );
        unset($employeeAttributes['shift']);
        $suffix = uniqid();
        $user = User::create($userAttributes + ['name' => 'Pegawai Test', 'username' => "pegawai{$suffix}", 'email' => "pegawai{$suffix}@test.local", 'password' => 'password', 'role' => 'karyawan', 'status' => 'aktif']);
        $employee = Karyawan::create($employeeAttributes + ['user_id' => $user->id, 'nama_lengkap' => $user->name, 'jabatan' => 'Barista', 'lokasi_kerja_id' => $location->id, 'default_shift_id' => $shift->id, 'tarif_per_jam' => 10000, 'tgl_bergabung' => '2026-01-01', 'status' => 'aktif']);

        return [$user, $employee, $location];
    }

    protected function createAdmin(array $attributes = []): User
    {
        $suffix = uniqid();

        return User::create($attributes + ['name' => 'Admin Test', 'username' => "admin{$suffix}", 'email' => "admin{$suffix}@test.local", 'password' => 'password', 'role' => 'admin', 'status' => 'aktif']);
    }

    protected function adminSession(User $admin): array
    {
        return ['admin_logged_in' => true, 'admin_user' => ['id' => $admin->id, 'nama' => $admin->name, 'email' => $admin->email]];
    }

    protected function createShift(array $attributes = []): Shift
    {
        return Shift::create($attributes + ['nama' => 'Shift '.uniqid(), 'jam_mulai' => '08:00', 'jam_selesai' => '16:00', 'toleransi_menit' => 15, 'is_aktif' => true]);
    }
}
