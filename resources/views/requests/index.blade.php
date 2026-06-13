@extends('layouts.app')

@section('title', 'Reestr')
@section('breadcrumb', 'Kadastr uchastkalari')

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
    <input name="q" value="{{ request('q') }}" placeholder="Kadastr, STIR/PINFL yoki egasi bo'yicha qidirish">
    <select name="status">
        <option value="">Barcha statuslar</option>
        @foreach($statuses as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>
        @endforeach
    </select>
    <select name="district_id">
        <option value="">Barcha tumanlar</option>
        @foreach($districts as $district)
            <option value="{{ $district->id }}" @selected((string) request('district_id') === (string) $district->id)>{{ $district->name }}</option>
        @endforeach
    </select>
    <input name="date_from" type="date" value="{{ request('date_from') }}">
    <input name="date_to" type="date" value="{{ request('date_to') }}">
    <button class="secondary-button" type="submit">Filter</button>
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
            <table>
                <thead><tr><th>Raqam</th><th>Egasi</th><th>Hudud</th><th>Kadastr</th><th>Status</th><th>Sana</th><th></th></tr></thead>
                <tbody>
                @foreach($requests as $item)
                    <tr>
                        <td>{{ $item->request_number }}</td>
                        <td>{{ $item->owner_name }}<small>{{ $item->owner_stir_pinfl }}</small></td>
                        <td>{{ $item->district->name }}<small>{{ $item->mahalla->name }}, {{ $item->street->name }}</small></td>
                        <td>{{ $item->building_cadastr_number }}</td>
                        <td><span class="status {{ $item->status }}">{{ str_replace('_', ' ', $item->status) }}</span></td>
                        <td>{{ $item->created_at->format('d.m.Y H:i') }}</td>
                        <td><a class="row-link" href="{{ route('requests.show', $item) }}">Ko'rish</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </section>
@endif
@endsection
