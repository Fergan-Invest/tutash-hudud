<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Mahalla;
use App\Models\RegistryRequest;
use App\Models\RequestFile;
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

    public function test_duplicate_building_cadastre_is_rejected_on_create(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $this->actingAs($user)
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect();

        $this->actingAs($user)
            ->from(route('requests.create'))
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect(route('requests.create'))
            ->assertSessionHasErrors('building_cadastr_number');

        $this->assertDatabaseCount('registry_requests', 1);
    }

    public function test_building_cadastre_can_stay_the_same_on_edit(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $this->actingAs($user)
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect();

        $registryRequest = RegistryRequest::firstOrFail();
        $payload = $this->payload($district, $mahalla, $street);
        unset($payload['images'], $payload['act_file']);
        $payload['owner_name'] = 'Same Cadastre Updated';

        $this->actingAs($user)
            ->put(route('requests.update', $registryRequest), $payload)
            ->assertRedirect(route('requests.show', $registryRequest));

        $this->assertDatabaseHas('registry_requests', [
            'id' => $registryRequest->id,
            'owner_name' => 'Same Cadastre Updated',
            'building_cadastr_number' => '31:23:12:31:23:1231/12:01',
        ]);
    }

    public function test_duplicate_building_cadastre_is_rejected_on_edit(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $this->actingAs($user)
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect();

        $payload = $this->payload($district, $mahalla, $street);
        $payload['building_cadastr_number'] = '31:23:12:31:23:1232/12:02';
        $payload['owner_name'] = 'Second Owner';
        $this->actingAs($user)->post(route('requests.store'), $payload)->assertRedirect();

        $second = RegistryRequest::where('owner_name', 'Second Owner')->firstOrFail();
        $payload = $this->payload($district, $mahalla, $street);
        unset($payload['images'], $payload['act_file']);
        $payload['building_cadastr_number'] = '31:23:12:31:23:1231/12:01';

        $this->actingAs($user)
            ->from(route('requests.edit', $second))
            ->put(route('requests.update', $second), $payload)
            ->assertRedirect(route('requests.edit', $second))
            ->assertSessionHasErrors('building_cadastr_number');
    }

    public function test_ajax_cadastre_check_reports_duplicates_and_ignores_current_request(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $this->actingAs($user)
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect();

        $registryRequest = RegistryRequest::firstOrFail();

        $this->actingAs($user)
            ->postJson(route('cadastre.check'), [
                'cadastre_number' => $registryRequest->building_cadastr_number,
            ])
            ->assertOk()
            ->assertJson(['restricted' => true]);

        $this->actingAs($user)
            ->postJson(route('cadastre.check'), [
                'cadastre_number' => $registryRequest->building_cadastr_number,
                'registry_request_id' => $registryRequest->id,
            ])
            ->assertOk()
            ->assertJson(['restricted' => false]);
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

    public function test_requests_index_can_filter_by_mahalla(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $otherMahalla = Mahalla::create(['district_id' => $district->id, 'name' => 'Yangi MFY']);
        $otherStreet = Street::create(['district_id' => $district->id, 'mahalla_id' => $otherMahalla->id, 'name' => 'Bobur', 'type' => 'kocha']);

        $payload = $this->payload($district, $mahalla, $street);
        $payload['owner_name'] = 'Oybek Owner';
        $this->actingAs($user)->post(route('requests.store'), $payload)->assertRedirect();

        $payload = $this->payload($district, $otherMahalla, $otherStreet);
        $payload['building_cadastr_number'] = '31:23:12:31:23:1232/12:02';
        $payload['owner_name'] = 'Yangi Owner';
        $this->actingAs($user)->post(route('requests.store'), $payload)->assertRedirect();

        $this->actingAs($user)
            ->get(route('requests.index', ['mahalla_id' => $otherMahalla->id]))
            ->assertOk()
            ->assertSee('name="mahalla_id" class="searchable-select"', false)
            ->assertSee('Barcha MFYlar')
            ->assertSee('Yangi Owner')
            ->assertDontSee('Oybek Owner');
    }

    public function test_requests_index_can_search_by_phone_digits(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $this->actingAs($user)
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('requests.index', ['q' => '901234567']))
            ->assertOk()
            ->assertSee('Owner MCHJ');
    }

    public function test_stir_and_pinfl_require_exact_lengths(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $payload = $this->payload($district, $mahalla, $street);
        $payload['owner_stir_pinfl'] = '1234567890';
        $payload['has_tenant'] = 1;
        $payload['tenant_stir_pinfl'] = '1234567890';
        $payload['tenant_name'] = 'Ijarachi';
        $payload['tenant_activity_type'] = 'Savdo';

        $this->actingAs($user)
            ->from(route('requests.create'))
            ->post(route('requests.store'), $payload)
            ->assertRedirect(route('requests.create'))
            ->assertSessionHasErrors(['owner_stir_pinfl', 'tenant_stir_pinfl']);
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

    public function test_requests_export_uses_current_filters(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $payload = $this->payload($district, $mahalla, $street);
        $payload['owner_name'] = 'Export Kocha Owner';
        $payload['street_type'] = 'kocha';
        $this->actingAs($user)->post(route('requests.store'), $payload)->assertRedirect();

        $payload = $this->payload($district, $mahalla, $street);
        $payload['building_cadastr_number'] = '31:23:12:31:23:1232/12:02';
        $payload['owner_name'] = 'Export Turizm Owner';
        $payload['street_type'] = 'turizm';
        $this->actingAs($user)->post(route('requests.store'), $payload)->assertRedirect();

        $response = $this->actingAs($user)->get(route('requests.export', ['street_type' => 'turizm']));

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.ms-excel', $response->headers->get('content-type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('Export Turizm Owner', $content);
        $this->assertStringNotContainsString('Export Kocha Owner', $content);
        $this->assertStringContainsString('mso-number-format:"0.00";', $content);
        $this->assertStringContainsString('mso-number-format:"\@";', $content);
        $this->assertStringContainsString('Akt fayli', $content);
        $this->assertStringContainsString('Loyiha kodi fayli', $content);
        $this->assertStringContainsString('Qayta o‘rganish akti', $content);
        $this->assertSame(1, substr_count($content, '>Mavjud<'));
        $this->assertSame(2, substr_count($content, '>Mavjud emas<'));
    }

    public function test_invest_can_delete_an_uploaded_request_file(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();

        $this->actingAs($user)
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect();

        $file = RequestFile::firstOrFail();
        Storage::disk('public')->assertExists($file->path);

        $this->actingAs($user)
            ->get(route('requests.edit', $file->registry_request_id))
            ->assertOk()
            ->assertSee(route('request-files.destroy', $file), false)
            ->assertSee('delete-existing-file', false);

        $this->actingAs($user)
            ->deleteJson(route('request-files.destroy', $file))
            ->assertOk()
            ->assertJson(['deleted' => true]);

        Storage::disk('public')->assertMissing($file->path);
        $this->assertDatabaseMissing('request_files', ['id' => $file->id]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $file->registry_request_id,
            'event' => 'file_deleted',
        ]);
    }

    public function test_tuman_cannot_delete_an_uploaded_request_file(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor('tuman');

        $this->actingAs($user)
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect();

        $file = RequestFile::firstOrFail();

        $this->actingAs($user)
            ->deleteJson(route('request-files.destroy', $file))
            ->assertForbidden();

        Storage::disk('public')->assertExists($file->path);
        $this->assertDatabaseHas('request_files', ['id' => $file->id]);
    }

    public function test_tuman_export_only_contains_own_district_data(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor('tuman');

        $this->actingAs($user)
            ->post(route('requests.store'), $this->payload($district, $mahalla, $street))
            ->assertRedirect();

        $otherDistrict = District::create(['external_id' => 2, 'name' => 'Other']);
        $otherMahalla = Mahalla::create(['district_id' => $otherDistrict->id, 'name' => 'Other mahalla']);
        $otherStreet = Street::create(['district_id' => $otherDistrict->id, 'mahalla_id' => $otherMahalla->id, 'name' => 'Other street', 'type' => 'kocha']);
        $invest = User::create(['name' => 'Invest', 'email' => 'export-invest@example.com', 'password' => 'secret', 'role' => 'invest']);
        $payload = $this->payload($otherDistrict, $otherMahalla, $otherStreet);
        $payload['owner_name'] = 'Other District Owner';
        $payload['building_cadastr_number'] = '31:23:12:31:23:1232/12:02';
        $this->actingAs($invest)->post(route('requests.store'), $payload)->assertRedirect();

        $content = $this->actingAs($user)
            ->get(route('requests.export', ['district_id' => $otherDistrict->id]))
            ->streamedContent();

        $this->assertStringContainsString('Owner MCHJ', $content);
        $this->assertStringNotContainsString('Other District Owner', $content);
    }

    public function test_hokimiyat_cadastre_is_optional_and_area_defaults_to_calculated_total(): void
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
            'total_area_manual' => false,
        ]);
    }

    public function test_total_area_can_be_entered_manually_when_checkbox_is_checked(): void
    {
        Storage::fake('public');
        [$user, $district, $mahalla, $street] = $this->setupActor();
        $payload = $this->payload($district, $mahalla, $street);
        $payload['area_length'] = 40;
        $payload['area_width'] = 20;
        $payload['calculated_land_area'] = 800;
        $payload['total_area'] = 750;
        $payload['total_area_manual'] = 1;

        $this->actingAs($user)
            ->post(route('requests.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('registry_requests', [
            'calculated_land_area' => 800,
            'total_area' => 750,
            'total_area_manual' => true,
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
