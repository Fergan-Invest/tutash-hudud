@extends('layouts.app')

@section('title', 'Manzillar')
@section('breadcrumb', 'Manzillar')

@section('content')
<section class="page-title">
    <div>
        <p class="eyebrow">Invest uchun manzil boshqaruvi</p>
        <h1>Tuman, MFY va ko‘chalar</h1>
        <p>Kerakli tumanni tanlang. Keyingi sahifada MFY va ko‘chalarni ko‘rish, qo‘shish yoki tahrirlash mumkin.</p>
    </div>
</section>

<section class="metrics address-summary">
    <article class="metric-card"><span>Tumanlar</span><strong>{{ $totalDistricts }}</strong><small>Farg‘ona viloyati bo‘yicha</small></article>
    <article class="metric-card"><span>MFYlar</span><strong>{{ $totalMahallas }}</strong><small>Tizimdagi jami MFY</small></article>
    <article class="metric-card"><span>Ko‘chalar</span><strong>{{ $totalStreets }}</strong><small>Tizimdagi jami ko‘cha</small></article>
</section>

<form class="panel address-search" method="GET">
    <input name="q" value="{{ request('q') }}" placeholder="Tuman nomi bo‘yicha qidirish">
    <button class="secondary-button" type="submit">Qidirish</button>
    @if(request('q'))<a class="ghost-button" href="{{ route('addresses.index') }}">Tozalash</a>@endif
</form>

<section class="address-grid">
    @forelse($districts as $district)
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
    @empty
        <div class="panel"><p class="muted-text">Tuman topilmadi.</p></div>
    @endforelse
</section>
@endsection
