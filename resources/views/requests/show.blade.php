@extends('layouts.app')

@section('title', $requestItem->request_number)
@section('breadcrumb', 'Kadastr uchastkalari')

@section('content')
<section class="page-title compact-title">
    <div>
        <h1>{{ $requestItem->request_number }}</h1>
        <p>{{ $requestItem->district->name }}, {{ $requestItem->mahalla->name }}, {{ $requestItem->street->name }}, {{ $requestItem->house_number }}</p>
    </div>
    <div class="title-actions">
        <span class="status {{ $requestItem->status }}">{{ str_replace('_', ' ', $requestItem->status) }}</span>
        @can('update', $requestItem)
            <a class="secondary-button" href="{{ route('requests.edit', $requestItem) }}">Tahrirlash</a>
        @endcan
    </div>
</section>

<section class="registry-card detail-workspace">
    <div class="detail-grid">
        <section class="panel inset-panel">
            <div class="panel-heading"><h2>Asosiy ma'lumotlar</h2></div>
            <div class="detail-list">
                <div><span>Kadastr</span><strong>{{ $requestItem->building_cadastr_number }}</strong></div>
                <div><span>Hokimiyat kadastri</span><strong>{{ $requestItem->hokimyatga_biriktirilgan_kadastr_raqami }}</strong></div>
                <div><span>Egasi</span><strong>{{ $requestItem->owner_name }} / {{ $requestItem->owner_stir_pinfl }}</strong></div>
                <div><span>Rahbar</span><strong>{{ $requestItem->director_name }}</strong></div>
                <div><span>Maydon</span><strong>{{ $requestItem->total_area }} m2</strong></div>
                <div><span>Faoliyat</span><strong>{{ $requestItem->activity_type }}</strong></div>
            </div>
        </section>

        <aside class="panel inset-panel">
            <div class="panel-heading"><h2>Yaratuvchi</h2></div>
            <div class="detail-list compact">
                <div><span>Kiritgan</span><strong>{{ $requestItem->creator->name }}</strong></div>
                <div><span>Holati</span><strong>{{ $requestItem->creator->isOnline() ? 'online' : 'offline' }}</strong></div>
                <div><span>Oxirgi IP</span><strong>{{ $requestItem->creator->last_ip ?? '-' }}</strong></div>
            </div>
        </aside>
    </div>

    <section class="panel inset-panel">
        <div class="panel-heading"><h2>Xarita</h2></div>
        <div id="show-map" class="leaflet-map" data-polygon='@json($requestItem->polygon_coordinates)'></div>
    </section>

    <section class="panel inset-panel">
        <div class="panel-heading"><h2>Rasmlar va fayllar</h2></div>
        @if($requestItem->images->isEmpty() && $requestItem->files->isEmpty())
            <div class="empty-inline">Sizda hali fayl yoki rasm yo'q.</div>
        @else
            <div class="document-grid">
                @foreach($requestItem->images as $image)
                    <a href="{{ Storage::url($image->path) }}" target="_blank">{{ $image->original_name }}</a>
                @endforeach
                @foreach($requestItem->files as $file)
                    <a href="{{ Storage::url($file->path) }}" target="_blank">{{ $file->type }}: {{ $file->original_name }}</a>
                @endforeach
            </div>
        @endif
    </section>

    <section class="panel inset-panel">
        <div class="panel-heading"><h2>O'zgarishlar tarixi</h2></div>
        @if($requestItem->audits->isEmpty())
            <div class="empty-inline">Sizda hali o'zgarishlar tarixi yo'q.</div>
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
                        <strong>{{ $audit->event }}</strong>
                        <span>{{ $audit->created_at->format('d.m.Y H:i') }} · {{ $audit->user?->name ?? 'Tizim' }} · IP: {{ $audit->ip_address ?? '-' }}</span>
                        <div class="change-list">
                            @forelse($changes as $column => [$old, $new])
                                <div class="change-row">
                                    <strong>{{ $column }}</strong>
                                    <code>{{ $formatValue($old) }}</code>
                                    <code>{{ $formatValue($new) }}</code>
                                </div>
                            @empty
                                <span>Ko'rsatiladigan o'zgarish yo'q.</span>
                            @endforelse
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</section>
@endsection
