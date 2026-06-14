<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Mahalla;
use App\Models\RegistryRequest;
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

    public function test_request_accepts_new_street_type_options(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $payload = $this->payload($district, $mahalla, $street);
        $payload['street_type'] = 'gostronomik';

        $this->actingAs($user)
            ->post(route('requests.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('registry_requests', ['street_type' => 'gostronomik']);
    }

    public function test_request_rejects_old_street_type_options(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $payload = $this->payload($district, $mahalla, $street);
        $payload['street_type'] = 'mavjud_emas';

        $this->actingAs($user)
            ->from(route('requests.create'))
            ->post(route('requests.store'), $payload)
            ->assertRedirect(route('requests.create'))
            ->assertSessionHasErrors('street_type');
    }

    public function test_requests_index_can_filter_by_street_type(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $payload = $this->payload($district, $mahalla, $street);
        $payload['owner_name'] = 'Kōcha Owner';
        $payload['street_type'] = 'kocha';
        $this->actingAs($user)->post(route('requests.store'), $payload)->assertRedirect();

        $payload = $this->payload($district, $mahalla, $street);
        $payload['building_cadastr_number'] = '31:23:12:31:23:1232/12:02';
        $payload['owner_name'] = 'Turizm Owner';
        $payload['street_type'] = 'turizm';
        $this->actingAs($user)->post(route('requests.store'), $payload)->assertRedirect();

        $this->actingAs($user)
            ->get(route('requests.index', ['street_type' => 'turizm']))
            ->assertOk()
            ->assertSee('T/r')
            ->assertSee('Yuborilgan')
            ->assertSee('Filtrlash')
            ->assertDontSee('THR-')
            ->assertSee('Turizm Owner')
            ->assertDontSee('Kōcha Owner');
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

    public function test_hokimiyat_cadastre_is_optional_and_area_uses_total_area(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $payload = $this->payload($district, $mahalla, $street);
        unset($payload['hokimyatga_biriktirilgan_kadastr_raqami'], $payload['calculated_land_area']);
        $payload['area_length'] = 40;
        $payload['area_width'] = 20;
        $payload['total_area'] = 1;

        $this->actingAs($user)
            ->post(route('requests.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('registry_requests', [
            'hokimyatga_biriktirilgan_kadastr_raqami' => null,
            'calculated_land_area' => 800,
            'total_area' => 800,
        ]);
    }

    public function test_adjacent_facilities_are_optional(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $payload = $this->payload($district, $mahalla, $street);
        unset($payload['adjacent_facilities']);

        $this->actingAs($user)
            ->post(route('requests.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('registry_requests', [
            'owner_name' => 'Owner MCHJ',
            'adjacent_facilities' => json_encode([]),
        ]);
    }

    public function test_image_validation_messages_are_uzbek(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $payload = $this->payload($district, $mahalla, $street);
        $payload['images'][2] = UploadedFile::fake()->create('not-image.pdf', 10, 'application/pdf');

        $this->actingAs($user)
            ->from(route('requests.create'))
            ->post(route('requests.store'), $payload)
            ->assertRedirect(route('requests.create'))
            ->assertSessionHasErrors('images.2');

        $errors = session('errors')->get('images.2');
        $this->assertStringContainsString('Yuklangan fayllar rasm bo‘lishi kerak.', implode(' ', $errors));
        $this->assertStringNotContainsString('field must be an image', implode(' ', $errors));
    }

    public function test_ajax_validate_checks_payload_without_storing(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $this->actingAs($user)
            ->postJson(route('requests.validate'), $this->payload($district, $mahalla, $street))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseCount('registry_requests', 0);
        $this->assertDatabaseCount('request_images', 0);
    }

    public function test_ajax_validate_returns_uzbek_errors(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $payload = $this->payload($district, $mahalla, $street);
        $payload['images'][2] = UploadedFile::fake()->create('not-image.pdf', 10, 'application/pdf');

        $this->actingAs($user)
            ->postJson(route('requests.validate'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('images.2')
            ->assertJsonFragment(['Yuklangan fayllar rasm bo‘lishi kerak.']);
    }

    public function test_ajax_validate_works_for_update_without_requiring_new_images(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $this->actingAs($user)
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect();

        $registryRequest = RegistryRequest::firstOrFail();
        $payload = $this->payload($district, $mahalla, $street);
        unset($payload['images'], $payload['act_file']);
        $payload['owner_name'] = 'Update Validate Owner';

        $this->actingAs($user)
            ->postJson(route('requests.validate', $registryRequest), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('registry_requests', [
            'id' => $registryRequest->id,
            'owner_name' => 'Update Validate Owner',
        ]);
    }

    public function test_show_page_renders_readonly_steps_and_image_previews(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $this->actingAs($user)
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect();

        $registryRequest = RegistryRequest::with('images')->firstOrFail();

        $this->actingAs($user)
            ->get(route('requests.show', $registryRequest))
            ->assertOk()
            ->assertSee('1. Egasi')
            ->assertSee('2. Manzil')
            ->assertSee('3. O‘lcham')
            ->assertSee('Rasmlar va fayllar')
            ->assertSee('readonly-media-card')
            ->assertSee($registryRequest->images->first()->original_name)
            ->assertSee('O‘zgarishlar tarixi')
            ->assertSee('Holati')
            ->assertSee('Oldin')
            ->assertSee('Keyin')
            ->assertSee('Yuborilgan')
            ->assertSee('O‘chirish')
            ->assertDontSee('Tutash hudud maydoni')
            ->assertDontSee('<strong>id</strong>', false);
    }

    public function test_create_form_does_not_render_adjacent_activity_land_field(): void
    {
        [$user] = $this->setupActor();

        $this->actingAs($user)
            ->get(route('requests.create'))
            ->assertOk()
            ->assertSee('Joylashuvni top')
            ->assertDontSee('Tutash hudud maydoni')
            ->assertDontSee('name="adjacent_activity_land"', false);
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

    public function test_request_can_be_updated_and_soft_deleted_without_media_cleanup(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $this->actingAs($user)
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect();

        $registryRequest = RegistryRequest::with(['images', 'files'])->firstOrFail();
        $storedImagePath = $registryRequest->images->first()->path;
        $storedFilePath = $registryRequest->files->first()->path;

        Storage::disk('public')->assertExists($storedImagePath);
        Storage::disk('public')->assertExists($storedFilePath);

        $payload = $this->payload($district, $mahalla, $street);
        unset($payload['images'], $payload['act_file']);
        $payload['owner_name'] = 'Updated Owner MCHJ';
        $payload['area_length'] = 12;
        $payload['area_width'] = 9;
        $payload['total_area'] = 108;
        $payload['calculated_land_area'] = 108;

        $this->actingAs($user)
            ->put(route('requests.update', $registryRequest), $payload)
            ->assertRedirect(route('requests.show', $registryRequest));

        $this->assertDatabaseHas('registry_requests', [
            'id' => $registryRequest->id,
            'owner_name' => 'Updated Owner MCHJ',
            'total_area' => 108,
        ]);
        $this->assertDatabaseCount('request_images', 4);

        $this->actingAs($user)
            ->delete(route('requests.destroy', $registryRequest))
            ->assertRedirect(route('requests.index'));

        $this->assertSoftDeleted('registry_requests', ['id' => $registryRequest->id]);
        $this->assertDatabaseCount('request_images', 4);
        $this->assertDatabaseCount('request_files', 1);
        Storage::disk('public')->assertExists($storedImagePath);
        Storage::disk('public')->assertExists($storedFilePath);
        Storage::disk('public')->assertExists("requests/{$registryRequest->id}");
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
