@extends('layouts.app')

@section('title', 'Arizalar xaritada')
@section('breadcrumb', 'Xaritada')

@section('content')
<section class="page-title compact-title">
    <div><h1>Arizalar xaritada</h1><p>Mavjud poligon va markerlar. Obyekt ustiga bosib batafsil ma'lumotni ko'ring.</p></div>
</section>
<section class="registry-card map-page-card" data-requests-map data-url="{{ route('requests.map-data') }}">
    <div class="map-page-filters">
        <label>Tuman
            <select data-map-district {{ auth()->user()->isTuman() ? 'disabled' : '' }}>
                <option value="">Barcha tumanlar</option>
                @foreach($districts as $district)<option value="{{ $district->id }}">{{ $district->name }}</option>@endforeach
            </select>
        </label>
        <label>Holati
            <select data-map-status><option value="">Barcha holatlar</option>@foreach($statuses as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach</select>
        </label>
        <label>Xarita turi
            <select data-map-type-select>
                <option value="hybrid" selected>Hybrid</option>
                <option value="satellite">Sun’iy yo‘ldosh</option>
                <option value="street">Oddiy xarita</option>
            </select>
        </label>
        <div class="map-object-count" data-map-count>Yuklanmoqda...</div>
    </div>
    <div id="requests-map" class="leaflet-map requests-overview-map" data-map-type="hybrid"></div>
</section>
@endsection
