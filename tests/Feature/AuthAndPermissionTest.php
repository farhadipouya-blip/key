<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\SettingSeeder::class);
    }

    private function makeUser(string $roleSlug, string $email): User
    {
        $user = User::create([
            'name' => 'کاربر ' . $roleSlug,
            'email' => $email,
            'password' => bcrypt('Admin@1234'),
            'is_active' => true,
        ]);
        $user->roles()->sync([\App\Models\Role::where('slug', $roleSlug)->first()->id]);

        return $user;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->makeUser('student', 'perm-student@key.test');

        $this->post(route('login.post'), [
            'identifier' => 'perm-student@key.test',
            'password' => 'Admin@1234',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->makeUser('student', 'perm-student2@key.test');

        $this->from(route('login'))->post(route('login.post'), [
            'identifier' => 'perm-student2@key.test',
            'password' => 'WrongPassword',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_student_cannot_access_admin_pages(): void
    {
        $user = $this->makeUser('student', 'perm-student3@key.test');
        $this->actingAs($user);

        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/roles')->assertForbidden();
        $this->get('/admin/settings')->assertForbidden();
        $this->get('/admin/shop/orders')->assertForbidden();
    }

    public function test_admin_can_access_admin_pages(): void
    {
        $user = $this->makeUser('admin', 'perm-admin@key.test');
        $this->actingAs($user);

        $this->get('/admin/users')->assertOk();
        $this->get('/admin/roles')->assertOk();
        $this->get('/admin/settings')->assertOk();
        $this->get('/admin/audit')->assertOk();
        $this->get('/admin/monitoring')->assertOk();
    }

    public function test_inactive_user_cannot_access_panel(): void
    {
        $user = User::create([
            'name' => 'غیرفعال',
            'email' => 'inactive@key.test',
            'password' => bcrypt('Admin@1234'),
            'is_active' => false,
        ]);
        $user->roles()->sync([\App\Models\Role::where('slug', 'student')->first()->id]);

        $this->actingAs($user);
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_dashboard_redirects_by_role(): void
    {
        $student = $this->makeUser('student', 'dash-student@key.test');
        $this->actingAs($student)->get('/dashboard')->assertRedirect(route('student.dashboard'));

        $admin = $this->makeUser('admin', 'dash-admin@key.test');
        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }
}
