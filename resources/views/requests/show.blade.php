@extends('layouts.app')

@php
    $statusLabels = [
        'draft' => 'Qoralama',
        'submitted' => 'Yuborilgan',
        'in_review' => 'Ko‘rib chiqilmoqda',
        'approved' => 'Tasdiqlangan',
        'rejected' => 'Rad etilgan',
    ];
    $ownerTypeLabels = ['jismoniy' => 'Jismoniy shaxs', 'yuridik' => 'Yuridik shaxs'];
    $streetTypeLabels = \App\Models\RegistryRequest::STREET_TYPES;
    $usagePurposeLabels = ['savdo' => 'Savdo', 'xizmat' => 'Xizmat', 'umumiy_ovqatlanish' => 'Umumiy ovqatlanish', 'boshqa' => 'Boshqa'];
    $fileTypeLabels = [
        'act_file' => 'Akt fayli',
        'design_code_file' => 'Loyiha kodi fayli',
        'qayta_organish_akti_file' => 'Qayta o‘rganish akti',
    ];
    $eventLabels = [
        'created' => 'Yaratildi',
        'updated' => 'Tahrirlandi',
        'deleted' => 'O‘chirildi',
        'image_deleted' => 'Rasm o‘chirildi',
        'street_created' => 'Ko‘cha yaratildi',
        'street_reused' => 'Mavjud ko‘cha ishlatildi',
    ];
    $yesNo = fn($value) => $value ? 'Ha' : 'Yo‘q';
    $display = fn($value) => filled($value) ? $value : '-';
@endphp

@section('title', $requestItem->request_number)
@section('breadcrumb', 'Kadastr uchastkalari')

@section('content')
<section class="page-title compact-title">
    <div>
        <h1>{{ $requestItem->request_number }}</h1>
        <p>{{ $requestItem->district->name }}, {{ $requestItem->mahalla->name }}, {{ $requestItem->street->name }}, {{ $requestItem->house_number }}</p>
    </div>
    <div class="title-actions">
        <span class="status {{ $requestItem->status }}">{{ $statusLabels[$requestItem->status] ?? $requestItem->status }}</span>
        @can('update', $requestItem)
            <a class="secondary-button" href="{{ route('requests.edit', $requestItem) }}">Tahrirlash</a>
        @endcan
    </div>
</section>

<section class="registry-card form-panel readonly-request">
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
                <div class="readonly-field wide"><span>Kadastr raqami</span><strong>{{ $display($requestItem->building_cadastr_number) }}</strong></div>
                <div class="readonly-field"><span>Hokimiyatga biriktirilgan kadastr raqami</span><strong>{{ $display($requestItem->hokimyatga_biriktirilgan_kadastr_raqami) }}</strong></div>
                <div class="readonly-field"><span>Mulk egasi turi</span><strong>{{ $ownerTypeLabels[$requestItem->owner_type] ?? $requestItem->owner_type }}</strong></div>
                <div class="readonly-field"><span>STIR/PINFL</span><strong>{{ $display($requestItem->owner_stir_pinfl) }}</strong></div>
                <div class="readonly-field"><span>Egasi nomi</span><strong>{{ $display($requestItem->owner_name) }}</strong></div>
                <div class="readonly-field"><span>Rahbar F.I.SH</span><strong>{{ $display($requestItem->director_name) }}</strong></div>
                <div class="readonly-field"><span>Telefon raqami</span><strong>{{ $display($requestItem->phone_number) }}</strong></div>
                <div class="readonly-field"><span>Yaratuvchi</span><strong>{{ $requestItem->creator->name }}</strong></div>
            </div>
        </div>
    </section>

    <section class="form-step-panel" data-step-panel="2">
        <div class="form-section">
            <h2>Manzil</h2>
            <div class="form-grid two">
                <div class="readonly-field"><span>Tuman</span><strong>{{ $requestItem->district->name }}</strong></div>
                <div class="readonly-field"><span>Mahalla</span><strong>{{ $requestItem->mahalla->name }}</strong></div>
                <div class="readonly-field"><span>Ko‘cha turi</span><strong>{{ $streetTypeLabels[$requestItem->street_type] ?? $requestItem->street_type }}</strong></div>
                <div class="readonly-field"><span>Ko‘cha</span><strong>{{ $requestItem->street->name }}</strong></div>
                <div class="readonly-field"><span>Uy raqami</span><strong>{{ $display($requestItem->house_number) }}</strong></div>
                <div class="readonly-field"><span>Ariza holati</span><strong>{{ $statusLabels[$requestItem->status] ?? $requestItem->status }}</strong></div>
            </div>
        </div>
    </section>

    <section class="form-step-panel" data-step-panel="3">
        <div class="form-section">
            <h2>O‘lchamlar va faoliyat</h2>
            <div class="form-grid three">
                <div class="readonly-field"><span>Uzunlik (m)</span><strong>{{ $requestItem->area_length }}</strong></div>
                <div class="readonly-field"><span>Kenglik (m)</span><strong>{{ $requestItem->area_width }}</strong></div>
                <div class="readonly-field"><span>Umumiy maydon (m²)</span><strong>{{ $requestItem->total_area }}</strong></div>
                <div class="readonly-field"><span>Fasad uzunligi (m)</span><strong>{{ $display($requestItem->building_facade_length) }}</strong></div>
                <div class="readonly-field"><span>Yozgi terassa tomonlari (m)</span><strong>{{ $display($requestItem->summer_terrace_sides) }}</strong></div>
                <div class="readonly-field"><span>Tutash hudud maydoni</span><strong>{{ $requestItem->adjacent_activity_land }}</strong></div>
                <div class="readonly-field"><span>Yo‘lgacha masofa (m)</span><strong>{{ $requestItem->distance_to_roadway }}</strong></div>
                <div class="readonly-field"><span>Trotuargacha masofa (m)</span><strong>{{ $requestItem->distance_to_sidewalk }}</strong></div>
                <div class="readonly-field"><span>Foydalanish maqsadi</span><strong>{{ $usagePurposeLabels[$requestItem->usage_purpose] ?? $requestItem->usage_purpose }}</strong></div>
                <div class="readonly-field"><span>Faoliyat turi</span><strong>{{ $display($requestItem->activity_type) }}</strong></div>
                <div class="readonly-field"><span>Tutash hududdagi faoliyat</span><strong>{{ $display($requestItem->adjacent_activity_type) }}</strong></div>
            </div>

            <div class="readonly-badges">
                <span>Terassada qurilmalar bor: <strong>{{ $yesNo($requestItem->terrace_buildings_available) }}</strong></span>
                <span>Doimiy qurilmalar bor: <strong>{{ $yesNo($requestItem->terrace_buildings_permanent) }}</strong></span>
                <span>Ruxsatnoma bor: <strong>{{ $yesNo($requestItem->has_permit) }}</strong></span>
                <span>Ijarachi mavjud: <strong>{{ $yesNo($requestItem->has_tenant) }}</strong></span>
            </div>

            @if(filled($requestItem->adjacent_facilities))
                <div class="readonly-badges">
                    @foreach($requestItem->adjacent_facilities as $facility)
                        <span>{{ str_replace('_', ' ', $facility) }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="form-section">
            <h2>Ijarachi va izoh</h2>
            <div class="form-grid two">
                <div class="readonly-field"><span>Ijarachi STIR/PINFL</span><strong>{{ $display($requestItem->tenant_stir_pinfl) }}</strong></div>
                <div class="readonly-field"><span>Ijarachi nomi / F.I.SH</span><strong>{{ $display($requestItem->tenant_name) }}</strong></div>
                <div class="readonly-field"><span>Ijarachi faoliyat turi</span><strong>{{ $display($requestItem->tenant_activity_type) }}</strong></div>
                <div class="readonly-field wide"><span>Qo‘shimcha ma’lumot</span><strong>{{ $display($requestItem->additional_info) }}</strong></div>
            </div>
        </div>
    </section>

    <section class="form-step-panel" data-step-panel="4">
        <div class="form-section">
            <h2>Xarita va poligon</h2>
            <div class="map-layout">
                <div class="map-shell">
                    <div id="show-map" class="leaflet-map" data-polygon='@json($requestItem->polygon_coordinates)'></div>
                </div>
                <div class="measure-panel">
                    <div class="readonly-field"><span>Xarita kengligi</span><strong>{{ $requestItem->latitude }}</strong></div>
                    <div class="readonly-field"><span>Xarita uzunligi</span><strong>{{ $requestItem->longitude }}</strong></div>
                    <div class="map-summary">Poligon xaritada ko‘rsatilgan. Xarita ustida yaqinlashtirib ko‘rishingiz mumkin.</div>
                </div>
            </div>
        </div>
    </section>

    <section class="form-step-panel" data-step-panel="5">
        <div class="form-section">
            <h2>Rasmlar va fayllar</h2>
            @if($requestItem->images->isEmpty() && $requestItem->files->isEmpty())
                <div class="empty-inline">Fayl yoki rasm yuklanmagan.</div>
            @else
                @if($requestItem->images->isNotEmpty())
                    <div class="readonly-media-grid">
                        @foreach($requestItem->images as $image)
                            <a class="readonly-media-card" href="{{ Storage::url($image->path) }}" target="_blank" rel="noopener">
                                <img src="{{ Storage::url($image->path) }}" alt="{{ $image->original_name }}">
                                <span>{{ $image->original_name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if($requestItem->files->isNotEmpty())
                    <div class="document-grid readonly-documents">
                        @foreach($requestItem->files as $file)
                            <a href="{{ Storage::url($file->path) }}" target="_blank" rel="noopener">{{ $fileTypeLabels[$file->type] ?? $file->type }}: {{ $file->original_name }}</a>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        <div class="form-section">
            <h2>O‘zgarishlar tarixi</h2>
            @if($requestItem->audits->isEmpty())
                <div class="empty-inline">O‘zgarishlar tarixi yo‘q.</div>
            @else
                <ol class="timeline">
                    @foreach($requestItem->audits as $audit)
                        @php
                            $oldValues = $audit->old_values ?? [];
                            $newValues = $audit->new_values ?? [];
                            $keys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
                            $changes = [];
                            foreach ($keys as $key) {
                                $old = $oldValues[$key] ?? null;
                                $new = $newValues[$key] ?? null;
                                if (json_encode($old) !== json_encode($new)) {
                                    $changes[$key] = [$old, $new];
                                }
                            }
                            $formatValue = function ($value) {
                                if (is_array($value)) {
                                    return json_encode($value, JSON_UNESCAPED_UNICODE);
                                }
                                return filled($value) ? (string) $value : '-';
                            };
                        @endphp
                        <li>
                            <strong>{{ $eventLabels[$audit->event] ?? $audit->event }}</strong>
                            <span>{{ $audit->created_at->format('d.m.Y H:i') }} · {{ $audit->user?->name ?? 'Tizim' }} · IP: {{ $audit->ip_address ?? '-' }}</span>
                            <div class="change-list">
                                @forelse($changes as $column => [$old, $new])
                                    <div class="change-row">
                                        <strong>{{ $column }}</strong>
                                        <code>{{ $formatValue($old) }}</code>
                                        <code>{{ $formatValue($new) }}</code>
                                    </div>
                                @empty
                                    <span>Ko‘rsatiladigan o‘zgarish yo‘q.</span>
                                @endforelse
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    </section>

    <div class="form-actions sticky-actions">
        <a class="ghost-button" href="{{ route('requests.index') }}">Ro‘yxatga qaytish</a>
        <button class="secondary-button" type="button" data-step-prev>Orqaga</button>
        <button class="secondary-button" type="button" data-step-next>Keyingisi</button>
        @can('update', $requestItem)
            <a class="primary-button" href="{{ route('requests.edit', $requestItem) }}">Tahrirlash</a>
        @endcan
    </div>
</section>
@endsection
