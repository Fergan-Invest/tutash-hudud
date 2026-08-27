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

<section class="metrics monitoring-metrics" aria-label="Xatlov bo‘yicha umumiy ko‘rsatkichlar">
    <article class="metric-card">
        <span>Jami xatlov</span>
        <strong>{{ number_format($totals['count'], 0, '.', ' ') }}</strong>
        <small>Tanlangan filterlar bo‘yicha</small>
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

<section class="monitoring-section-heading">
    <h2>Shartnomalar bo‘yicha</h2>
    <span>{{ $totals['payment_percent'] }}% to‘langan</span>
</section>
<section class="metrics monitoring-metrics contract-metrics" aria-label="Shartnomalar bo‘yicha umumiy ko‘rsatkichlar">
    <article class="metric-card">
        <span>Jami shartnoma</span>
        <strong>{{ number_format($totals['contracts'], 0, '.', ' ') }}</strong>
        <small>Shartnoma tuzilganlar</small>
    </article>
    <article class="metric-card">
        <span>To‘langan</span>
        <strong>{{ number_format($totals['paid'], 0, '.', ' ') }}</strong>
        <small>{{ $totals['payment_percent'] }}% shartnoma bo‘yicha</small>
    </article>
    <article class="metric-card">
        <span>To‘lanmagan</span>
        <strong>{{ number_format($totals['unpaid'], 0, '.', ' ') }}</strong>
        <small>To‘lov kutilmoqda</small>
    </article>
</section>

<section class="panel table-panel registry-card monitoring-table-card">
    <div class="table-card-heading">
        <h2>Tumanlar kesimida</h2>
        <span>{{ number_format($rows->count(), 0, '.', ' ') }} ta hudud</span>
    </div>
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
                    <th>Jami shartnoma <small>Shartnoma tuzilgan</small></th>
                    <th>Buyurtmachi imzolagan</th>
                    <th>To‘langan <small>To‘lov to‘langan</small></th>
                    <th>To‘lanmagan</th>
                    <th>To‘lov holati</th>
                    <th>Ko‘rish</th>
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
                        <td><strong>{{ number_format($row['process_statuses']['contract_concluded'] ?? 0, 0, '.', ' ') }}</strong></td>
                        <td>{{ number_format($row['process_statuses']['customer_signed'] ?? 0, 0, '.', ' ') }}</td>
                        <td><span class="payment-badge payment-badge-paid">{{ number_format($row['process_statuses']['payment_paid'] ?? 0, 0, '.', ' ') }}</span></td>
                        <td><span class="payment-badge payment-badge-unpaid">{{ number_format($row['process_statuses']['payment_unpaid'] ?? 0, 0, '.', ' ') }}</span></td>
                        <td>
                            <div class="table-payment-progress">
                                <span><i style="width: {{ $row['process_statuses']['payment_percent'] ?? 0 }}%"></i></span>
                                <b>{{ $row['process_statuses']['payment_percent'] ?? 0 }}%</b>
                            </div>
                        </td>
                        <td><a class="row-link" href="{{ $row['url'] }}">Ro‘yxat</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 11 + count($streetTypes) }}" class="empty">Ma’lumot topilmadi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
