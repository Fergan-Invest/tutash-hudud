@extends('layouts.app')

@php
    $editing = filled($requestItem);
    $field = fn($name, $default = null) => old($name, $requestItem?->{$name} ?? $default);
    $polygon = old('polygon_coordinates', $requestItem?->polygon_coordinates ? json_encode($requestItem->polygon_coordinates) : '');
    $ownerType = $field('owner_type', 'yuridik');
@endphp

@section('title', $editing ? 'Arizani tahrirlash' : 'Yangi ariza')
@section('breadcrumb', $editing ? 'Arizani tahrirlash' : 'Yangi ariza')

@section('content')
<section class="page-title">
    <div>
        <p class="eyebrow">{{ $editing ? 'Tahrirlash' : 'Yaratish' }}</p>
        <h1>{{ $editing ? $requestItem->request_number : 'Yangi ariza' }}</h1>
    </div>
</section>

<form class="panel form-panel stepped-form" method="POST" enctype="multipart/form-data" action="{{ $editing ? route('requests.update', $requestItem) : route('requests.store') }}">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="stepper" aria-label="Ariza bosqichlari">
        <button class="step active" type="button" data-step-target="1">1. Egasi</button>
        <button class="step" type="button" data-step-target="2">2. Manzil</button>
        <button class="step" type="button" data-step-target="3">3. O‘lcham</button>
        <button class="step" type="button" data-step-target="4">4. Xarita</button>
        <button class="step" type="button" data-step-target="5">5. Fayllar</button>
    </div>

    <section class="form-step-panel active" data-step-panel="1">
        <div class="form-section">
            <h2>Egasi va kadastr</h2>
            <div class="form-grid two">
                <label class="wide">Kadastr raqami
                    <input type="text" id="building_cadastr_number" name="building_cadastr_number" value="{{ $field('building_cadastr_number') }}" required placeholder="Masalan: 31:23:12:31:23:1231/12:01" maxlength="100" autocomplete="off">
                    @error('building_cadastr_number')<span>{{ $message }}</span>@enderror
                </label>

                <div class="alert hidden wide" id="cadastre-restriction-warning">
                    <strong id="cadastre-restriction-title">Kadastr tekshiruvi</strong>
                    <p id="cadastre-restriction-message"></p>
                </div>

                <label>Hokimiyatga biriktirilgan kadastr raqami
                    <input id="hokimyatga_biriktirilgan_kadastr_raqami" name="hokimyatga_biriktirilgan_kadastr_raqami" value="{{ $field('hokimyatga_biriktirilgan_kadastr_raqami') }}" required placeholder="10:08:04:01:02:5006/0001:035" maxlength="100" autocomplete="off">
                    @error('hokimyatga_biriktirilgan_kadastr_raqami')<span>{{ $message }}</span>@enderror
                </label>

                <fieldset class="segmented-field">
                    <legend>Mulk egasi turi</legend>
                    <label><input type="radio" name="owner_type" value="yuridik" @checked($ownerType === 'yuridik')> Yuridik shaxs</label>
                    <label><input type="radio" name="owner_type" value="jismoniy" @checked($ownerType === 'jismoniy')> Jismoniy shaxs</label>
                    @error('owner_type')<span>{{ $message }}</span>@enderror
                </fieldset>

                <label><span id="owner-identifier-label">{{ $ownerType === 'jismoniy' ? 'PINFL' : 'STIR' }}</span>
                    <input id="owner_stir_pinfl" name="owner_stir_pinfl" inputmode="numeric" value="{{ $field('owner_stir_pinfl') }}" required placeholder="{{ $ownerType === 'jismoniy' ? '14 xonali PINFL' : '9 xonali STIR' }}">
                    @error('owner_stir_pinfl')<span>{{ $message }}</span>@enderror
                </label>

                <label id="owner-name-label">{{ $ownerType === 'jismoniy' ? 'F.I.SH' : 'Korxona nomi' }}
                    <input name="owner_name" value="{{ $field('owner_name') }}" required>
                    @error('owner_name')<span>{{ $message }}</span>@enderror
                </label>

                <label>Rahbar F.I.SH
                    <input name="director_name" value="{{ $field('director_name') }}" required>
                    @error('director_name')<span>{{ $message }}</span>@enderror
                </label>

                <label>Telefon raqami
                    <input id="phone_number" name="phone_number" value="{{ $field('phone_number') }}" placeholder="+998 (90) 123-45-67" inputmode="numeric">
                    @error('phone_number')<span>{{ $message }}</span>@enderror
                </label>
            </div>
        </div>
    </section>

    <section class="form-step-panel" data-step-panel="2">
        <div class="form-section">
            <h2>Manzil</h2>
            <div class="form-grid two">
                <label>Tuman
                    <select name="district_id" id="district_id" required>
                        <option value="">Tanlang</option>
                        @foreach($districts as $district)<option value="{{ $district->id }}" @selected((string) $field('district_id') === (string) $district->id)>{{ $district->name }}</option>@endforeach
                    </select>
                    @error('district_id')<span>{{ $message }}</span>@enderror
                </label>
                <label>Mahalla
                    <select name="mahalla_id" id="mahalla_id" required>
                        <option value="">Tanlang</option>
                        @foreach($mahallas as $mahalla)<option value="{{ $mahalla->id }}" data-district="{{ $mahalla->district_id }}" @selected((string) $field('mahalla_id') === (string) $mahalla->id)>{{ $mahalla->name }}</option>@endforeach
                    </select>
                    @error('mahalla_id')<span>{{ $message }}</span>@enderror
                </label>
                <label>Ko‘cha turi
                    <select name="street_type" id="street_type" required>@foreach($streetTypes as $key => $label)<option value="{{ $key }}" @selected($field('street_type', 'kocha') === $key)>{{ $label }}</option>@endforeach</select>
                    @error('street_type')<span>{{ $message }}</span>@enderror
                </label>
                <label>Ko‘cha
                    <div class="inline-field">
                        <select name="street_id" id="street_id" required>
                            <option value="">Tanlang</option>
                            @foreach($streets as $street)<option value="{{ $street->id }}" data-district="{{ $street->district_id }}" data-mahalla="{{ $street->mahalla_id }}" @selected((string) $field('street_id') === (string) $street->id)>{{ $street->name }}</option>@endforeach
                        </select>
                        <button class="icon-button" type="button" id="add-street">+</button>
                    </div>
                    @error('street_id')<span>{{ $message }}</span>@enderror
                </label>
                <label>Uy raqami
                    <input name="house_number" value="{{ $field('house_number') }}" required>
                    @error('house_number')<span>{{ $message }}</span>@enderror
                </label>
                <label class="wide hidden" id="new-street-wrap">Yangi ko‘cha nomi
                    <input id="new_street_name">
                    <small>Nomni yozib, + tugmasini yana bosing.</small>
                </label>
            </div>
        </div>
    </section>

    <section class="form-step-panel" data-step-panel="3">
        <div class="form-section">
            <h2>O‘lchamlar va faoliyat</h2>
            <div class="form-grid three">
                <label>Uzunlik (m)<input id="area_length" name="area_length" type="number" step="0.01" value="{{ $field('area_length') }}" required>@error('area_length')<span>{{ $message }}</span>@enderror</label>
                <label>Kenglik (m)<input id="area_width" name="area_width" type="number" step="0.01" value="{{ $field('area_width') }}" required>@error('area_width')<span>{{ $message }}</span>@enderror</label>
                <label>Hisoblangan maydon<input id="calculated_land_area" name="calculated_land_area" type="number" step="0.01" value="{{ $field('calculated_land_area') }}" readonly required>@error('calculated_land_area')<span>{{ $message }}</span>@enderror</label>
                <label>Umumiy maydon (m²)<input id="total_area" name="total_area" type="number" step="0.01" value="{{ $field('total_area') }}" required>@error('total_area')<span>{{ $message }}</span>@enderror</label>
                <label>Fasad uzunligi (m)<input name="building_facade_length" type="number" step="0.01" value="{{ $field('building_facade_length') }}">@error('building_facade_length')<span>{{ $message }}</span>@enderror</label>
                <label>Yozgi terassa tomonlari (m)<input name="summer_terrace_sides" type="number" step="0.01" value="{{ $field('summer_terrace_sides') }}">@error('summer_terrace_sides')<span>{{ $message }}</span>@enderror</label>
                <label>Yo‘lgacha masofa (m)<input name="distance_to_roadway" type="number" step="0.01" value="{{ $field('distance_to_roadway') }}" required>@error('distance_to_roadway')<span>{{ $message }}</span>@enderror</label>
                <label>Trotuargacha masofa (m)<input name="distance_to_sidewalk" type="number" step="0.01" value="{{ $field('distance_to_sidewalk') }}" required>@error('distance_to_sidewalk')<span>{{ $message }}</span>@enderror</label>
                <label>Tutash hudud maydoni<input name="adjacent_activity_land" type="number" step="0.01" value="{{ $field('adjacent_activity_land') }}" required>@error('adjacent_activity_land')<span>{{ $message }}</span>@enderror</label>
                <label>Foydalanish maqsadi<select name="usage_purpose" required>@foreach($usagePurposes as $key => $label)<option value="{{ $key }}" @selected($field('usage_purpose') === $key)>{{ $label }}</option>@endforeach</select>@error('usage_purpose')<span>{{ $message }}</span>@enderror</label>
                <label>Faoliyat turi<input name="activity_type" value="{{ $field('activity_type') }}" required>@error('activity_type')<span>{{ $message }}</span>@enderror</label>
                <label>Tutash hududdagi faoliyat<input name="adjacent_activity_type" value="{{ $field('adjacent_activity_type') }}">@error('adjacent_activity_type')<span>{{ $message }}</span>@enderror</label>
            </div>

            <div class="check-grid">
                <label><input type="hidden" name="terrace_buildings_available" value="0"><input type="checkbox" name="terrace_buildings_available" value="1" @checked($field('terrace_buildings_available'))> Terassada qurilmalar bor</label>
                <label><input type="hidden" name="terrace_buildings_permanent" value="0"><input type="checkbox" name="terrace_buildings_permanent" value="1" @checked($field('terrace_buildings_permanent'))> Doimiy qurilmalar bor</label>
                <label><input type="hidden" name="has_permit" value="0"><input type="checkbox" name="has_permit" value="1" @checked($field('has_permit'))> Ruxsatnoma bor</label>
                <label><input type="hidden" name="has_tenant" value="0"><input type="checkbox" name="has_tenant" value="1" @checked($field('has_tenant'))> Ijarachi mavjud</label>
            </div>
            <div class="check-grid">@foreach($facilities as $facility)<label><input name="adjacent_facilities[]" value="{{ $facility }}" type="checkbox" @checked(in_array($facility, old('adjacent_facilities', $requestItem?->adjacent_facilities ?? []), true))> {{ str_replace('_', ' ', $facility) }}</label>@endforeach</div>
            @error('adjacent_facilities')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="form-section">
            <h2>Ijarachi va izoh</h2>
            <div class="form-grid two">
                <label>Ijarachi STIR/PINFL<input name="tenant_stir_pinfl" value="{{ $field('tenant_stir_pinfl') }}">@error('tenant_stir_pinfl')<span>{{ $message }}</span>@enderror</label>
                <label>Ijarachi nomi / F.I.SH<input name="tenant_name" value="{{ $field('tenant_name') }}">@error('tenant_name')<span>{{ $message }}</span>@enderror</label>
                <label>Ijarachi faoliyat turi<input name="tenant_activity_type" value="{{ $field('tenant_activity_type') }}">@error('tenant_activity_type')<span>{{ $message }}</span>@enderror</label>
                <label class="wide">Qo‘shimcha ma’lumot<textarea name="additional_info">{{ $field('additional_info') }}</textarea>@error('additional_info')<span>{{ $message }}</span>@enderror</label>
            </div>
        </div>
    </section>

    <section class="form-step-panel" data-step-panel="4">
        <div class="form-section">
            <h2>Xarita va poligon</h2>
            <div class="map-layout">
                <div class="map-shell">
                    <div class="map-toolbar" aria-label="Xarita asboblari">
                        <button type="button" class="map-tool active" id="draw-polygon">Chizish</button>
                        <button type="button" class="map-tool" id="undo-point">Oxirgi nuqta</button>
                        <button type="button" class="map-tool" id="fit-polygon">Ko‘rsatish</button>
                        <button type="button" class="map-tool danger" id="reset-polygon">Tozalash</button>
                    </div>
                    <div id="polygon-map" class="leaflet-map" data-polygon='@json($polygon)'></div>
                </div>
                <div class="measure-panel">
                    <div class="map-help">Xaritani bosing: nuqta qo‘shiladi. Nuqtani sudrab tahrirlang. Marker ustida o‘ng tugma: nuqtani o‘chirish.</div>
                    <input type="hidden" id="latitude" name="latitude" value="{{ $field('latitude') }}" required>
                    <input type="hidden" id="longitude" name="longitude" value="{{ $field('longitude') }}" required>
                    <input type="hidden" id="polygon_coordinates" name="polygon_coordinates" value="{{ $polygon }}" required>
                    @error('latitude')<p class="field-error">{{ $message }}</p>@enderror
                    @error('longitude')<p class="field-error">{{ $message }}</p>@enderror
                    @error('polygon_coordinates')<p class="field-error">{{ $message }}</p>@enderror
                    <div class="map-summary" id="polygon-summary">Poligon hali chizilmagan.</div>
                </div>
            </div>
        </div>
    </section>

    <section class="form-step-panel" data-step-panel="5">
        <div class="form-section">
            <h2>Rasmlar va fayllar</h2>
            <div class="form-grid two">
                @if($editing && $requestItem->images->isNotEmpty())
                    <div class="wide existing-image-grid">
                        @foreach($requestItem->images as $image)
                            <article class="image-slot existing-image" data-image-id="{{ $image->id }}">
                                <img src="{{ Storage::url($image->path) }}" alt="{{ $image->original_name }}">
                                <div>
                                    <strong>{{ $image->original_name }}</strong>
                                    <button type="button" class="secondary-button delete-existing-image" data-delete-url="{{ route('request-images.destroy', $image) }}">O‘chirish</button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif

                <div class="wide image-upload-grid">
                    @for($i = 1; $i <= 4; $i++)
                        <label class="image-slot" data-image-slot>
                            <span>Rasm {{ $i }}</span>
                            <img class="image-preview hidden" alt="Rasm {{ $i }} preview">
                            <input name="images[]" type="file" accept="image/*" capture="environment" {{ $editing ? '' : 'required' }}>
                            <button type="button" class="ghost-button image-clear hidden">Tanlangan rasmni o‘chirish</button>
                            <small>Kamera yoki galereyadan rasm tanlang.</small>
                        </label>
                    @endfor
                    @error('images')<p class="field-error">{{ $message }}</p>@enderror
                    @error('images.*')<p class="field-error">{{ $message }}</p>@enderror
                </div>

                <label>Akt fayli<input name="act_file" type="file" accept=".pdf,image/*" {{ $editing ? '' : 'required' }}>@error('act_file')<span>{{ $message }}</span>@enderror</label>
                <label>Loyiha kodi fayli<input name="design_code_file" type="file" accept=".pdf,image/*">@error('design_code_file')<span>{{ $message }}</span>@enderror</label>
                <label>Qayta o‘rganish akti<input name="qayta_organish_akti_file" type="file" accept=".pdf,image/*">@error('qayta_organish_akti_file')<span>{{ $message }}</span>@enderror</label>
            </div>
        </div>
    </section>

    <div class="form-actions sticky-actions">
        <a class="ghost-button" href="{{ route('requests.index') }}">Bekor qilish</a>
        <button class="secondary-button" type="button" data-step-prev>Orqaga</button>
        <button class="secondary-button" type="button" data-step-next>Keyingisi</button>
        <button class="primary-button" type="submit">Saqlash</button>
    </div>
</form>
@endsection
