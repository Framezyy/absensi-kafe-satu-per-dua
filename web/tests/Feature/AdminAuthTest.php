<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_fake_stale_nonadmin_and_inactive_sessions_are_rejected(): void
    {
        $this->withSession(['admin_logged_in' => true, 'admin_user' => ['id' => 999]])->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        [$employee] = $this->createEmployee();
        $this->withSession($this->adminSession($employee))->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $admin = $this->createAdmin(['status' => 'nonaktif']);
        $this->withSession($this->adminSession($admin))->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_valid_admin_can_access_login_and_logout(): void
    {
        $admin = $this->createAdmin(['username' => 'realadmin']);
        $this->post(route('admin.login.submit'), ['username' => 'REALADMIN', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'))->assertSessionHas('admin_user.id', $admin->id);
        $this->withSession($this->adminSession($admin))->get(route('admin.dashboard'))->assertOk();
        $this->withSession($this->adminSession($admin))->post(route('admin.logout'))->assertRedirect(route('admin.login'));
    }

    public function test_invalid_admin_login_is_rejected(): void
    {
        $this->createAdmin(['username' => 'adminwrong']);
        $this->from(route('admin.login'))->post(route('admin.login.submit'), ['username' => 'adminwrong', 'password' => 'wrong'])
            ->assertRedirect(route('admin.login'))->assertSessionHasErrors('username');
    }
}
