@extends('layouts.app')

@section('title', 'Manzillar')
@section('breadcrumb', 'Manzillar')

@section('content')
<section class="page-title">
    <div>
        <p class="eyebrow">Manzil ma’lumotlari</p>
        <h1>Tuman, MFY va ko‘chalar</h1>
        <p>Qaysi tumanda nechta MFY va ko‘cha borligini tez ko‘rish uchun.</p>
    </div>
</section>

<section class="metrics address-summary">
    <article class="metric-card"><span>Tumanlar</span><strong>{{ $totalDistricts }}</strong><small>Farg‘ona viloyati bo‘yicha</small></article>
    <article class="metric-card"><span>MFYlar</span><strong>{{ $totalMahallas }}</strong><small>CSVdan import qilingan</small></article>
    <article class="metric-card"><span>Ko‘chalar</span><strong>{{ $totalStreets }}</strong><small>Tizimga qo‘shilgan</small></article>
</section>

<form class="panel address-search" method="GET">
    <input name="q" value="{{ request('q') }}" placeholder="Tuman yoki MFY nomi bo‘yicha qidirish">
    <button class="secondary-button" type="submit">Qidirish</button>
    @if(request('q'))<a class="ghost-button" href="{{ route('addresses.index') }}">Tozalash</a>@endif
</form>

<section class="address-grid">
    @foreach($districts as $district)
        <a class="address-card" href="{{ route('addresses.show', $district) }}">
            <div>
                <strong>{{ $district->name }}</strong>
                <span>District ID: {{ $district->external_id }}</span>
            </div>
            <div class="address-card-stats">
                <span>{{ $district->mahallas_count }} MFY</span>
                <span>{{ $district->streets_count }} ko‘cha</span>
            </div>
        </a>
    @endforeach
</section>

@endsection
