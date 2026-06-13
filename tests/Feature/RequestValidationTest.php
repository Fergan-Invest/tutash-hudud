<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Mahalla;
use App\Models\Street;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invest_can_create_request_with_four_unique_images(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $this->actingAs($user)
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect();

        $this->assertDatabaseCount('registry_requests', 1);
        $this->assertDatabaseCount('request_images', 4);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_cadastre_number_accepts_colon_suffix(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $payload = $this->payload($district, $mahalla, $street);
        $payload['building_cadastr_number'] = '31:23:12:31:23:1232:12321312321';
        $payload['hokimyatga_biriktirilgan_kadastr_raqami'] = '10:08:04:01:02:5006:0001035';

        $this->actingAs($user)
            ->post(route('requests.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('registry_requests', [
            'building_cadastr_number' => '31:23:12:31:23:1232:12321312321',
            'hokimyatga_biriktirilgan_kadastr_raqami' => '10:08:04:01:02:5006:0001035',
        ]);
    }

    public function test_request_accepts_missing_street_type_option(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $payload = $this->payload($district, $mahalla, $street);
        $payload['street_type'] = 'mavjud_emas';

        $this->actingAs($user)
            ->post(route('requests.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('registry_requests', ['street_type' => 'mavjud_emas']);
    }

    public function test_act_file_is_optional(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $payload = $this->payload($district, $mahalla, $street);
        unset($payload['act_file']);

        $this->actingAs($user)
            ->post(route('requests.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseCount('registry_requests', 1);
    }

    public function test_duplicate_images_are_rejected_and_old_input_returns(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $payload = $this->payload($district, $mahalla, $street);
        $same = $this->png('same.png', 'same-content');
        $payload['images'] = [$same, $this->png('same-copy.png', 'same-content'), $this->png('a.png', 'a'), $this->png('b.png', 'b')];

        $this->actingAs($user)
            ->from(route('requests.create'))
            ->post(route('requests.store'), $payload)
            ->assertRedirect(route('requests.create'))
            ->assertSessionHasErrors('images.1')
            ->assertSessionHasInput('owner_name', 'Owner MCHJ');
    }

    public function test_tuman_cannot_submit_another_district(): void
    {
        [$user, $district, $mahalla, $street] = $this->setupActor('tuman');
        $other = District::create(['external_id' => 99, 'name' => 'Other']);
        $payload = $this->payload($district, $mahalla, $street);
        $payload['district_id'] = $other->id;

        $this->actingAs($user)
            ->post(route('requests.store'), $payload)
            ->assertSessionHasErrors('district_id');
    }

    private function setupActor(string $role = 'invest'): array
    {
        $district = District::create(['external_id' => 1, 'name' => 'Farg‘ona shahar']);
        $mahalla = Mahalla::create(['district_id' => $district->id, 'name' => 'Oybek']);
        $street = Street::create(['district_id' => $district->id, 'mahalla_id' => $mahalla->id, 'name' => 'Navoiy', 'type' => 'kocha']);
        $user = User::create(['name' => 'User', 'email' => 'user@example.com', 'password' => 'secret', 'role' => $role, 'district_id' => $role === 'tuman' ? $district->id : null]);

        return [$user, $district, $mahalla, $street];
    }

    private function payload(District $district, Mahalla $mahalla, Street $street): array
    {
        return [
            'building_cadastr_number' => '31:23:12:31:23:1231/12:01',
            'hokimyatga_biriktirilgan_kadastr_raqami' => '10:08:04:01:02:5006/0001:035',
            'owner_type' => 'yuridik',
            'owner_stir_pinfl' => '123456789',
            'owner_name' => 'Owner MCHJ',
            'district_id' => $district->id,
            'mahalla_id' => $mahalla->id,
            'street_id' => $street->id,
            'house_number' => '12',
            'street_type' => 'kocha',
            'director_name' => 'Ali Valiyev',
            'phone_number' => '+998 (90) 123-45-67',
            'area_length' => 10,
            'area_width' => 8,
            'calculated_land_area' => 80,
            'total_area' => 80,
            'building_facade_length' => 5,
            'summer_terrace_sides' => 4,
            'distance_to_roadway' => 2,
            'distance_to_sidewalk' => 1,
            'usage_purpose' => 'savdo',
            'activity_type' => 'Savdo',
            'terrace_buildings_available' => 0,
            'terrace_buildings_permanent' => 0,
            'has_permit' => 1,
            'has_tenant' => 0,
            'adjacent_activity_land' => 80,
            'adjacent_facilities' => ['soyabon'],
            'latitude' => 40.3777,
            'longitude' => 71.7978,
            'polygon_coordinates' => json_encode(['type' => 'Feature', 'geometry' => ['type' => 'Polygon', 'coordinates' => [[[71, 40], [71.1, 40], [71.1, 40.1], [71, 40]]]]]),
            'images' => [$this->png('1.png', '1'), $this->png('2.png', '2'), $this->png('3.png', '3'), $this->png('4.png', '4')],
            'act_file' => UploadedFile::fake()->create('act.pdf', 10, 'application/pdf'),
        ];
    }

    private function png(string $name, string $salt): UploadedFile
    {
        $base = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=');
        return UploadedFile::fake()->createWithContent($name, $base.$salt);
    }
}
