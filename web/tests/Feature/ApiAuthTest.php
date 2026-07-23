<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_employee_with_active_profile_can_login(): void
    {
        [$user] = $this->createEmployee(['username' => 'mobileuser']);

        $this->postJson('/api/auth/login', ['username' => ' MOBILEUSER ', 'password' => 'password'])
            ->assertOk()->assertJsonStructure(['token', 'user'])->assertJsonPath('user.status_aktif', true);
        $this->assertCount(1, $user->tokens);
    }

    public function test_admin_and_user_without_profile_are_rejected_without_server_error(): void
    {
        $admin = $this->createAdmin(['username' => 'adminlogin']);
        $orphan = $this->createAdmin(['username' => 'orphan', 'role' => 'karyawan']);

        $this->postJson('/api/auth/login', ['username' => $admin->username, 'password' => 'password'])->assertUnprocessable();
        $this->postJson('/api/auth/login', ['username' => $orphan->username, 'password' => 'password'])->assertUnprocessable();
    }

    public function test_inactive_user_or_profile_cannot_login(): void
    {
        [$inactiveUser] = $this->createEmployee(['username' => 'inactiveuser', 'status' => 'nonaktif']);
        [$inactiveProfile, $profile] = $this->createEmployee(['username' => 'inactiveprofile']);
        $profile->update(['status' => 'nonaktif']);

        $this->postJson('/api/auth/login', ['username' => $inactiveUser->username, 'password' => 'password'])->assertUnprocessable();
        $this->postJson('/api/auth/login', ['username' => $inactiveProfile->username, 'password' => 'password'])->assertUnprocessable();
    }

    public function test_me_logout_and_stale_token_invalidation(): void
    {
        [$user] = $this->createEmployee();
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('id', $user->id);
        $user->update(['status' => 'nonaktif']);
        $this->getJson('/api/auth/me')->assertForbidden()->assertJsonPath('code', 'ACCOUNT_INACTIVE');

        $user->update(['status' => 'aktif']);
        $this->postJson('/api/auth/logout')->assertOk();
    }
}
