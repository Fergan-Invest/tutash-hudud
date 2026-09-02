<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_invest_can_create_a_district_user(): void
    {
        $invest = User::factory()->create(['role' => 'invest', 'email' => 'invest@tutash.local']);
        $district = District::create(['external_id' => 1, 'name' => 'Farg‘ona tumani']);

        $this->actingAs($invest)->post(route('users.store'), [
            'name' => 'Yangi operator',
            'email' => 'yangi.operator@example.com',
            'role' => 'tuman',
            'district_id' => $district->id,
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertRedirect()->assertSessionHas('success');

        $user = User::where('email', 'yangi.operator@example.com')->firstOrFail();
        $this->assertSame('tuman', $user->role);
        $this->assertSame($district->id, $user->district_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('StrongPassword123!', $user->password));
    }

    public function test_district_is_required_when_creating_a_tuman_user(): void
    {
        $invest = User::factory()->create(['role' => 'invest', 'email' => 'invest@tutash.local']);

        $this->actingAs($invest)->from(route('users.index'))->post(route('users.store'), [
            'name' => 'Tumansiz operator',
            'email' => 'tumansiz@example.com',
            'role' => 'tuman',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertRedirect(route('users.index'))->assertSessionHasErrors('district_id');

        $this->assertDatabaseMissing('users', ['email' => 'tumansiz@example.com']);
    }

    public function test_create_user_form_has_password_visibility_buttons(): void
    {
        $invest = User::factory()->create(['role' => 'invest', 'email' => 'invest@tutash.local']);

        $this->actingAs($invest)->get(route('users.index'))
            ->assertOk()
            ->assertSee('aria-controls="new-user-password"', false)
            ->assertSee('aria-controls="new-user-password-confirmation"', false);
    }

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
