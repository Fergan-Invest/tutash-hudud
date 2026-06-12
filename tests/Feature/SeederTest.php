<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
