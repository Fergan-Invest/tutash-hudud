@extends('layouts.app')

@section('title', 'Reestr')
@section('breadcrumb', 'Kadastr uchastkalari')
@section('topbar-actions')
    <a class="secondary-button topbar-export-button" href="{{ route('requests.monitoring', request()->query()) }}">Monitoring</a>
    <a class="secondary-button topbar-export-button" href="{{ route('requests.export', request()->query()) }}">Excel</a>
@endsection

@php
    $fileTypeLabels = [
        'act_file' => 'Akt',
        'design_code_file' => 'Loyiha',
        'qayta_organish_akti_file' => 'Qayta',
    ];
@endphp

@section('content')
<section class="page-title compact-title">
    <div>
        <h1>Kadastr uchastkalari</h1>
    </div>
    @can('create', App\Models\RegistryRequest::class)
        <a class="primary-button" href="{{ route('requests.create') }}">Ariza berish</a>
    @endcan
</section>

<form class="panel filters soft-panel" method="GET">
    <input name="q" value="{{ request('q') }}" placeholder="Kadastr, STIR/PINFL, telefon yoki egasi bo'yicha qidirish">
    <select name="street_type">
        <option value="">Barcha ko‘cha turlari</option>
        @foreach($streetTypes as $key => $label)
            <option value="{{ $key }}" @selected(request('street_type') === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="district_id">
        <option value="">Barcha tumanlar</option>
        @foreach($districts as $district)
            <option value="{{ $district->id }}" @selected((string) request('district_id') === (string) $district->id)>{{ $district->name }}</option>
        @endforeach
    </select>
    <select name="mahalla_id" class="searchable-select">
        <option value="">Barcha MFYlar</option>
        @foreach($mahallas as $mahalla)
            <option value="{{ $mahalla->id }}" @selected((string) request('mahalla_id') === (string) $mahalla->id)>{{ $mahalla->name }}</option>
        @endforeach
    </select>
    <input name="date_from" type="date" value="{{ request('date_from') }}">
    <input name="date_to" type="date" value="{{ request('date_to') }}">
    <select name="per_page" aria-label="Sahifadagi qatorlar">
        @foreach($perPageOptions as $option)
            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }} qator</option>
        @endforeach
    </select>
    <button class="secondary-button" type="submit">Filtrlash</button>
</form>

@if($requests->isEmpty())
    <section class="empty-state-card" aria-label="Ma'lumot yo'q">
        <div class="empty-illustration">
            <svg viewBox="0 0 96 96" aria-hidden="true">
                <rect x="24" y="26" width="42" height="48" rx="8"/>
                <path d="M32 38h21M32 48h16M32 58h12"/>
                <rect x="36" y="18" width="42" height="48" rx="8"/>
                <path d="M62 34v18M53 43h18"/>
            </svg>
        </div>
        <h2>Sizda hali ma'lumot yo'q</h2>
        <p>Siz hali tutash hudud bo'yicha birorta ariza kiritmagansiz.</p>
        @can('create', App\Models\RegistryRequest::class)
            <a class="primary-button empty-action" href="{{ route('requests.create') }}">Ariza berish</a>
        @endcan
    </section>
@else
    <section class="panel table-panel registry-card">
        <div class="table-wrap">
            <table class="registry-table">
                <thead><tr><th>T/r</th><th>Egasi</th><th>Hudud</th><th>Ko‘cha turi</th><th>Kadastr</th><th>Hokimiyatga biriktirilgan kadastr raqami</th><th>Fayllar</th><th>Sana</th><th></th></tr></thead>
                <tbody>
                @foreach($requests as $item)
                    @php
                        $uploadedFileTypes = $item->files->pluck('type')->flip();
                    @endphp
                    <tr class="clickable-row" data-href="{{ route('requests.show', $item) }}" onclick="window.location=this.dataset.href">
                        <td><span class="row-number">{{ $requests->firstItem() + $loop->index }}</span></td>
                        <td>{{ $item->owner_name }}<small>{{ $item->owner_stir_pinfl }}</small></td>
                        <td>{{ $item->district->name }}<small>{{ $item->mahalla->name }}, {{ $item->street->name }}</small></td>
                        <td>{{ $streetTypes[$item->street_type] ?? $item->street_type }}</td>
                        <td>{{ $item->building_cadastr_number }}</td>
                        <td>{{ $item->hokimyatga_biriktirilgan_kadastr_raqami ?: '-' }}</td>
                        <td>
                            <div class="file-status-list">
                                @foreach($fileTypeLabels as $type => $label)
                                    <span class="file-status-chip {{ $uploadedFileTypes->has($type) ? 'uploaded' : 'missing' }}">
                                        {{ $label }} <b>{{ $uploadedFileTypes->has($type) ? '✓' : '-' }}</b>
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td>{{ $item->created_at->format('d.m.Y H:i') }}</td>
                        <td><a class="row-link row-action-button" href="{{ route('requests.show', $item) }}" onclick="event.stopPropagation()">Kirish</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination-bar">
            <p>
                {{ $requests->firstItem() }}-{{ $requests->lastItem() }}
                / {{ $requests->total() }} ta yozuv
            </p>

            @if($requests->hasPages())
                @php
                    $paginationPages = collect([1, $requests->currentPage() - 2, $requests->currentPage() - 1, $requests->currentPage(), $requests->currentPage() + 1, $requests->currentPage() + 2, $requests->lastPage()])
                        ->filter(fn ($page) => $page >= 1 && $page <= $requests->lastPage())
                        ->unique()
                        ->sort()
                        ->values();
                    $previousRenderedPage = null;
                @endphp
                <nav class="pagination-links" aria-label="Sahifalash">
                    @if($requests->onFirstPage())
                        <span class="pagination-link disabled" aria-disabled="true">Oldingi</span>
                    @else
                        <a class="pagination-link" href="{{ $requests->previousPageUrl() }}" rel="prev">Oldingi</a>
                    @endif

                    @foreach($paginationPages as $page)
                        @if($previousRenderedPage !== null && $page > $previousRenderedPage + 1)
                            <span class="pagination-gap" aria-hidden="true">...</span>
                        @endif

                        @if($page === $requests->currentPage())
                            <span class="pagination-link active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pagination-link" href="{{ $requests->url($page) }}">{{ $page }}</a>
                        @endif

                        @php($previousRenderedPage = $page)
                    @endforeach

                    @if($requests->hasMorePages())
                        <a class="pagination-link" href="{{ $requests->nextPageUrl() }}" rel="next">Keyingi</a>
                    @else
                        <span class="pagination-link disabled" aria-disabled="true">Keyingi</span>
                    @endif
                </nav>
            @endif
        </div>
    </section>
@endif
@endsection
