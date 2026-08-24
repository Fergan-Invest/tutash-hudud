<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_invest_can_change_another_users_password(): void
    {
        $invest = User::factory()->create(['role' => 'invest', 'email' => 'invest@tutash.local']);
        $user = User::factory()->create(['role' => 'tuman']);

        $this->actingAs($invest)->patch(route('users.password.update', $user), [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_invest_can_disable_and_reenable_another_user(): void
    {
        $invest = User::factory()->create(['role' => 'invest', 'email' => 'invest@tutash.local']);
        $user = User::factory()->create(['role' => 'tuman']);

        $this->actingAs($invest)->patch(route('users.status.update', $user), ['is_active' => 0])->assertRedirect();
        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $this->actingAs($invest)->patch(route('users.status.update', $user), ['is_active' => 1])->assertRedirect();
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_non_invest_cannot_manage_users(): void
    {
        $user = User::factory()->create(['role' => 'tuman']);
        $target = User::factory()->create(['role' => 'tuman']);

        $this->actingAs($user)->get(route('users.index'))->assertForbidden();
        $this->actingAs($user)->patch(route('users.status.update', $target), ['is_active' => 0])->assertForbidden();
    }

    public function test_invest_cannot_disable_own_account(): void
    {
        $invest = User::factory()->create(['role' => 'invest', 'email' => 'invest@tutash.local']);

        $this->actingAs($invest)
            ->patch(route('users.status.update', $invest), ['is_active' => 0])
            ->assertStatus(422);

        $this->assertTrue($invest->fresh()->is_active);
    }

    public function test_disabled_user_cannot_login_or_keep_using_a_session(): void
    {
        $user = User::factory()->create([
            'role' => 'tuman',
            'password' => Hash::make('Password123!'),
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)->get(route('requests.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
