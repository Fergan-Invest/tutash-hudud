<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserPasswordSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_imports_addresses_and_users_idempotently(): void
    {
        $this->seed();
        $firstDistrictCount = \App\Models\District::count();
        $firstMahallaCount = \App\Models\Mahalla::count();
        $this->seed();

        $this->assertSame($firstDistrictCount, \App\Models\District::count());
        $this->assertSame($firstMahallaCount, \App\Models\Mahalla::count());
        $this->assertDatabaseHas('users', ['email' => 'invest@tutash.local', 'role' => 'invest']);
        $this->assertDatabaseHas('users', ['email' => 'viloyat.hokimi@tutash.local', 'role' => 'viloyat_hokimi']);
    }

    public function test_user_password_seeder_updates_login_account_passwords(): void
    {
        $this->seed();
        $this->seed(UserPasswordSeeder::class);

        $passwords = [
            'invest@tutash.local' => '@ew1411ADBiQ#@!a',
            'viloyat.hokimi@tutash.local' => '@3224ew1411ADBiQ#@!a',
            'district-1@tutash.local' => '@3456@#gfh#@!a',
        ];

        foreach ($passwords as $email => $password) {
            $this->assertTrue(Hash::check($password, User::where('email', $email)->firstOrFail()->password));
        }
    }
}
