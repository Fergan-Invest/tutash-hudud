@extends('layouts.app')

@section('title', 'Monitoring')
@section('breadcrumb', 'Monitoring')
@section('topbar-actions')
    <a class="secondary-button topbar-export-button" href="{{ route('requests.index', request()->query()) }}">Reestr</a>
    <a class="secondary-button topbar-export-button" href="{{ route('requests.export', request()->query()) }}">Excel</a>
@endsection

@section('content')
<section class="page-title compact-title">
    <div>
        <h1>Monitoring</h1>
        <p>{{ number_format($totals['count'], 0, '.', ' ') }} ta xatlovga {{ number_format($totals['total_area'], 2, '.', ' ') }} kv/m maydon xatlandi</p>
    </div>
</section>

<section class="metrics monitoring-metrics">
    <article class="metric-card">
        <span>Jami xatlov</span>
        <strong>{{ number_format($totals['count'], 0, '.', ' ') }}</strong>
        <small>Tanlangan filterlar bo'yicha</small>
    </article>
    <article class="metric-card">
        <span>Jami maydon</span>
        <strong>{{ number_format($totals['total_area'], 2, '.', ' ') }}</strong>
        <small>kv/m</small>
    </article>
    <article class="metric-card">
        <span>Faol tumanlar</span>
        <strong>{{ number_format($totals['districts'], 0, '.', ' ') }}</strong>
        <small>Xatlov kiritilgan hududlar</small>
    </article>
</section>

<form class="panel filters soft-panel" method="GET">
    <input name="q" value="{{ request('q') }}" placeholder="Kadastr, STIR/PINFL yoki egasi bo'yicha qidirish">
    <select name="status">
        <option value="">Barcha statuslar</option>
        @foreach($statuses as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $statusLabels[$status] ?? $status }}</option>
        @endforeach
    </select>
    <select name="street_type">
        <option value="">Barcha ko'cha turlari</option>
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
    <button class="secondary-button" type="submit">Filtrlash</button>
</form>

<section class="panel table-panel registry-card">
    <div class="table-wrap">
        <table class="registry-table monitoring-table">
            <thead>
                <tr>
                    <th>Tuman</th>
                    <th>Xatlov soni</th>
                    <th>Maydon</th>
                    @foreach($streetTypes as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                    <th>Tasdiqlangan</th>
                    <th>Ko'rish</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="clickable-row" onclick="window.location='{{ $row['url'] }}'">
                        <td><strong>{{ $row['district']->name }}</strong></td>
                        <td>{{ number_format($row['count'], 0, '.', ' ') }}</td>
                        <td>{{ number_format($row['total_area'], 2, '.', ' ') }} <small>kv/m</small></td>
                        @foreach($streetTypes as $key => $label)
                            <td>{{ number_format($row['street_types'][$key] ?? 0, 0, '.', ' ') }}</td>
                        @endforeach
                        <td>{{ number_format($row['statuses']['approved'] ?? 0, 0, '.', ' ') }}</td>
                        <td><a class="row-link" href="{{ $row['url'] }}">Ro'yxat</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 6 + count($streetTypes) }}" class="empty">Ma'lumot topilmadi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
