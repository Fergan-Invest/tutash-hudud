@extends('layouts.app')

@section('title', 'Reestr')
@section('breadcrumb', 'Arizalar reestri')

@section('content')
<section class="page-title">
    <div>
        <p class="eyebrow">Reestr</p>
        <h1>Tutash hududlar arizalari</h1>
    </div>
    @can('create', App\Models\RegistryRequest::class)
        <a class="primary-button" href="{{ route('requests.create') }}">+ Yangi ariza</a>
    @endcan
</section>

<form class="panel filters" method="GET">
    <input name="q" value="{{ request('q') }}" placeholder="Kadastr, STIR/PINFL yoki egasi bo‘yicha qidirish">
    <select name="status"><option value="">Barcha statuslar</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_', ' ', $status) }}</option>@endforeach</select>
    <select name="district_id"><option value="">Barcha tumanlar</option>@foreach($districts as $district)<option value="{{ $district->id }}" @selected((string) request('district_id') === (string) $district->id)>{{ $district->name }}</option>@endforeach</select>
    <input name="date_from" type="date" value="{{ request('date_from') }}">
    <input name="date_to" type="date" value="{{ request('date_to') }}">
    <button class="secondary-button" type="submit">Filter</button>
</form>

<section class="panel table-panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Raqam</th><th>Egasi</th><th>Hudud</th><th>Kadastr</th><th>Status</th><th>Sana</th><th></th></tr></thead>
            <tbody>
            @forelse($requests as $item)
                <tr>
                    <td>{{ $item->request_number }}</td>
                    <td>{{ $item->owner_name }}<small>{{ $item->owner_stir_pinfl }}</small></td>
                    <td>{{ $item->district->name }}<small>{{ $item->mahalla->name }}, {{ $item->street->name }}</small></td>
                    <td>{{ $item->building_cadastr_number }}</td>
                    <td><span class="status {{ $item->status }}">{{ str_replace('_', ' ', $item->status) }}</span></td>
                    <td>{{ $item->created_at->format('d.m.Y H:i') }}</td>
                    <td><a class="row-link" href="{{ route('requests.show', $item) }}">Ko‘rish</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Ma’lumot topilmadi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $requests->links() }}
</section>
@endsection
