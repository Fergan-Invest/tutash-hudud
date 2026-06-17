@extends('layouts.app')

@section('title', 'Monitoring')
@section('breadcrumb', 'Monitoring')
@section('topbar-actions')
    <a class="secondary-button topbar-export-button" href="{{ route('requests.index', request()->query()) }}">Reestr</a>
    <a class="secondary-button topbar-export-button" href="{{ route('requests.export', request()->query()) }}">Excel</a>
@endsection

@php
    $districtTotal = max($districts->count(), 1);
    $activePercent = (int) round(($totals['districts'] / $districtTotal) * 100);
    $mapRows = $rows->map(function ($row) use ($streetTypes) {
        return [
            'name' => $row['district']->name,
            'count' => (int) $row['count'],
            'total_area' => (float) $row['total_area'],
            'total_area_label' => number_format((float) $row['total_area'], 2, '.', ' '),
            'approved' => (int) ($row['statuses']['approved'] ?? 0),
            'street_types' => collect($streetTypes)->mapWithKeys(fn ($label, $key) => [
                $label => (int) ($row['street_types'][$key] ?? 0),
            ])->all(),
            'url' => $row['url'],
        ];
    })->values();
@endphp

@section('content')
<section class="map-monitoring-shell" data-monitoring-map>
    <script type="application/json" data-monitoring-districts>
        @json($mapRows)
    </script>

    <header class="map-monitoring-header">
        <div>
            <h1>Monitoring xaritasi</h1>
            <p>Farg'ona viloyati bo'yicha xatlov ko'rsatkichlari</p>
        </div>
        <div class="map-monitoring-totals">
            <span>Jami xatlov <b>{{ number_format((int) $totals['count'], 0, '.', ' ') }}</b></span>
            <span>Jami maydon <b>{{ number_format((float) $totals['total_area'], 2, '.', ' ') }}</b> kv/m</span>
        </div>
    </header>

    <div class="map-monitoring-workspace">
        <div class="fergana-map-canvas" aria-label="Farg'ona viloyati interaktiv xaritasi" data-map-src="{{ asset('dataset/map/fergana-map.svg') }}">
            <div class="map-selected-label" data-map-selected-label>Monitoring</div>
        </div>

        <aside class="map-district-panel" data-monitoring-panel>
            <span class="panel-eyebrow">Shahar va tumanlar</span>
            <h2 data-panel-name>Hududni tanlang</h2>
            <p data-panel-summary>Xaritadagi tumanni bosing, shu hudud bo'yicha xatlov va maydon ma'lumotlari shu yerda ochiladi.</p>

            <div class="panel-metrics">
                <div><span>Xatlov soni</span><strong data-panel-count>0</strong></div>
                <div><span>Maydon <small>kv/m</small></span><strong data-panel-area>0.00</strong></div>
            </div>

            <div class="panel-type-list" data-panel-types></div>

            <a class="primary-button panel-link" href="{{ route('requests.index') }}" data-panel-link>Ro'yxatni ochish</a>
        </aside>
    </div>
</section>
@endsection
