<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Mahalla;
use App\Models\RegistryRequest;
use App\Models\Street;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_works_with_seeded_style_user(): void
    {
        User::create([
            'name' => 'Invest',
            'email' => 'invest@tutash.local',
            'password' => Hash::make('Password123!'),
            'role' => 'invest',
        ]);

        $this->post('/login', [
            'email' => 'invest@tutash.local',
            'password' => 'Password123!',
        ])->assertRedirect('/requests');

        $this->assertAuthenticated();
    }

    public function test_tuman_user_only_sees_own_district_request(): void
    {
        [$districtA, $districtB] = [District::create(['external_id' => 1, 'name' => 'A']), District::create(['external_id' => 2, 'name' => 'B'])];
        $user = User::create(['name' => 'A operator', 'email' => 'a@example.com', 'password' => 'secret', 'role' => 'tuman', 'district_id' => $districtA->id]);
        $requestA = $this->registryRequest($districtA, $user);
        $requestB = $this->registryRequest($districtB, $user);

        $this->actingAs($user)->get(route('requests.show', $requestA))->assertOk();
        $this->actingAs($user)->get(route('requests.show', $requestB))->assertForbidden();
    }

    public function test_viloyat_hokimi_cannot_create(): void
    {
        $user = User::create(['name' => 'Hokim', 'email' => 'h@example.com', 'password' => 'secret', 'role' => 'viloyat_hokimi']);

        $this->actingAs($user)->get(route('requests.create'))->assertForbidden();
    }

    public function test_invest_create_form_renders(): void
    {
        District::create(['external_id' => 1, 'name' => 'Farg‘ona shahar']);
        $user = User::create(['name' => 'Invest', 'email' => 'invest-form@example.com', 'password' => 'secret', 'role' => 'invest']);

        $this->actingAs($user)
            ->get(route('requests.create'))
            ->assertOk()
            ->assertSee('Yangi ariza')
            ->assertSee('1. Egasi');
    }

    public function test_addresses_page_renders_counts(): void
    {
        $district = District::create(['external_id' => 1, 'name' => 'Farg‘ona shahar']);
        Mahalla::create(['district_id' => $district->id, 'name' => 'Oybek']);
        $user = User::create(['name' => 'Invest', 'email' => 'address@example.com', 'password' => 'secret', 'role' => 'invest']);

        $this->actingAs($user)
            ->get(route('addresses.index'))
            ->assertOk()
            ->assertSee('Farg‘ona shahar')
            ->assertSee('1 MFY')
            ->assertDontSee('MFYlar ro‘yxati');

        $this->actingAs($user)
            ->get(route('addresses.show', $district))
            ->assertOk()
            ->assertSee('MFYlar ro‘yxati')
            ->assertSee('Oybek');
    }

    private function registryRequest(District $district, User $user): RegistryRequest
    {
        $mahalla = Mahalla::create(['district_id' => $district->id, 'name' => 'Markaz']);
        $street = Street::create(['district_id' => $district->id, 'mahalla_id' => $mahalla->id, 'name' => 'Navoiy', 'type' => 'kocha']);

        return RegistryRequest::create([
            'request_number' => uniqid('THR-'),
            'status' => 'submitted',
            'created_by' => $user->id,
            'building_cadastr_number' => '31:23:12:31:23:1231/12:01',
            'hokimyatga_biriktirilgan_kadastr_raqami' => '10:08:04:01:02:5006/0001:035',
            'owner_type' => 'yuridik',
            'owner_stir_pinfl' => '123456789',
            'owner_name' => 'Owner',
            'district_id' => $district->id,
            'mahalla_id' => $mahalla->id,
            'street_id' => $street->id,
            'house_number' => '1',
            'street_type' => 'kocha',
            'director_name' => 'Director',
            'area_length' => 10,
            'area_width' => 10,
            'calculated_land_area' => 100,
            'total_area' => 100,
            'distance_to_roadway' => 1,
            'distance_to_sidewalk' => 1,
            'usage_purpose' => 'savdo',
            'activity_type' => 'Savdo',
            'terrace_buildings_available' => false,
            'terrace_buildings_permanent' => false,
            'has_permit' => true,
            'adjacent_activity_land' => 100,
            'adjacent_facilities' => ['soyabon'],
            'latitude' => 40.1,
            'longitude' => 71.1,
            'polygon_coordinates' => ['type' => 'Feature', 'geometry' => ['type' => 'Polygon', 'coordinates' => [[[71, 40], [71.1, 40], [71.1, 40.1], [71, 40]]]]],
        ]);
    }
}
