@extends('layouts.app')

@section('title', $district->name)
@section('breadcrumb', $district->name)

@section('content')
<section class="page-title">
    <div>
        <p class="eyebrow">Invest uchun manzil boshqaruvi</p>
        <h1>{{ $district->name }}</h1>
        <p>MFYni tanlab, unga tegishli ko‘chalarni boshqaring.</p>
    </div>
    <a class="secondary-button" href="{{ route('addresses.index') }}">Tumanlarga qaytish</a>
</section>

<section class="district-statistics" aria-labelledby="statistics-title">
    <div class="statistics-heading">
        <div>
            <p class="eyebrow">Hudud statistikasi</p>
            <h2 id="statistics-title">{{ $statistics['period_label'] }}gi xatlovlar</h2>
        </div>
        <nav class="period-tabs" aria-label="Statistika davri">
            <a href="{{ route('addresses.show', ['district' => $district, 'period' => 'today']) }}" class="{{ $statistics['period'] === 'today' ? 'active' : '' }}">Bugun</a>
            <a href="{{ route('addresses.show', ['district' => $district, 'period' => 'week']) }}" class="{{ $statistics['period'] === 'week' ? 'active' : '' }}">Bu hafta</a>
            <a href="{{ route('addresses.show', ['district' => $district, 'period' => 'month']) }}" class="{{ $statistics['period'] === 'month' ? 'active' : '' }}">Bu oy</a>
        </nav>
    </div>

    <form class="statistics-date-filter" method="GET" action="{{ route('addresses.show', $district) }}">
        <input type="hidden" name="period" value="custom">
        <label>Boshlanish sanasi
            <input type="date" name="date_from" value="{{ $statistics['date_from'] }}" required>
        </label>
        <label>Tugash sanasi
            <input type="date" name="date_to" value="{{ $statistics['date_to'] }}" required>
        </label>
        <button class="primary-button" type="submit">Ko‘rsatish</button>
    </form>

    <div class="statistics-summary-grid">
        <article class="statistics-card accent-card">
            <span>Xatlovlar soni</span>
            <strong>{{ number_format($statistics['count'], 0, '.', ' ') }}</strong>
            <small>{{ $statistics['period_label'] }} qo‘shilgan</small>
        </article>
        <article class="statistics-card">
            <span>Jami maydon</span>
            <strong>{{ number_format($statistics['area'], 2, '.', ' ') }}</strong>
            <small>kv/m hisobida</small>
        </article>
        <article class="statistics-card">
            <span>Faol MFYlar</span>
            <strong>{{ number_format($statistics['active_mahallas'], 0, '.', ' ') }}</strong>
            <small>{{ $district->mahallas_count }} ta MFYdan</small>
        </article>
    </div>

    <div class="statistics-visual-grid">
        <article class="panel statistics-chart-card">
            <div class="panel-heading">
                <div><h3>Qo‘shilish dinamikasi</h3><p>{{ $statistics['period'] === 'today' ? 'Soatlar' : 'Kunlar' }} kesimida</p></div>
                <span class="chart-legend"><i></i> Xatlovlar</span>
            </div>
            <div class="timeline-chart" aria-label="Qo‘shilish vaqti diagrammasi">
                @foreach($statistics['timeline'] as $point)
                    @php
                        $height = $point['count']
                            ? max(10, round($point['count'] / $statistics['max_timeline_count'] * 100))
                            : 3;
                    @endphp
                    <div class="timeline-column" title="{{ $point['label'] }} — {{ $point['count'] }} ta, {{ number_format($point['area'], 2, '.', ' ') }} kv/m">
                        <span class="timeline-value">{{ $point['count'] ?: '' }}</span>
                        <i style="height: {{ $height }}%"></i>
                        <small>{{ $point['label'] }}</small>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="panel statistics-chart-card">
            <div class="panel-heading">
                <div><h3>MFYlar kesimida</h3><p>Eng ko‘p xatlov kiritilgan hududlar</p></div>
            </div>
            <div class="mahalla-bars">
                @forelse($statistics['mahallas']->take(8) as $row)
                    <a class="mahalla-bar-row" href="{{ route('requests.index', ['district_id' => $district->id, 'mahalla_id' => $row['id']]) }}">
                        <div><strong>{{ $row['name'] }}</strong><span>{{ number_format($row['area'], 2, '.', ' ') }} kv/m</span></div>
                        <div class="mahalla-bar-track"><i style="width: {{ max(4, round($row['count'] / $statistics['max_mahalla_count'] * 100)) }}%"></i></div>
                        <b>{{ $row['count'] }}</b>
                    </a>
                @empty
                    <div class="statistics-empty">Tanlangan davrda xatlov qo‘shilmagan.</div>
                @endforelse
            </div>
        </article>
    </div>

    @if($statistics['recent']->isNotEmpty())
        <details class="panel recent-statistics">
            <summary>Oxirgi qo‘shilganlar <span>{{ $statistics['recent']->count() }} ta</span></summary>
            <div class="recent-registry-list">
                @foreach($statistics['recent'] as $item)
                    <a href="{{ route('requests.show', $item) }}">
                        <span class="recent-time">{{ $item->created_at->format('H:i') }}</span>
                        <strong>{{ $statistics['mahalla_names'][$item->mahalla_id] ?? 'MFY ko‘rsatilmagan' }}</strong>
                        <span>{{ $item->request_number }}</span>
                        <b>{{ number_format((float) $item->total_area, 2, '.', ' ') }} kv/m</b>
                    </a>
                @endforeach
            </div>
        </details>
    @endif
</section>

<section class="metrics address-summary">
    <article class="metric-card"><span>MFYlar</span><strong>{{ $district->mahallas_count }}</strong><small>{{ $district->name }} bo‘yicha</small></article>
    <article class="metric-card"><span>Ko‘chalar</span><strong>{{ $district->streets_count }}</strong><small>Tizimga qo‘shilgan</small></article>
</section>

<details class="panel address-editor" @if($errors->has('district_id')) open @endif>
    <summary>Yangi MFY qo‘shish</summary>
    <form method="POST" action="{{ route('mahallas.store') }}" class="form-grid two">
        @csrf
        <input type="hidden" name="district_id" value="{{ $district->id }}">
        <label>MFY nomi
            <input name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Masalan: Oybek MFY">
            @error('name')<span>{{ $message }}</span>@enderror
        </label>
        <div class="address-form-action"><button class="primary-button" type="submit">MFY qo‘shish</button></div>
    </form>
</details>

<section class="panel table-panel">
    <div class="panel-heading">
        <h2>MFYlar ro‘yxati</h2>
        <span class="muted-text">{{ $mahallas->total() }} ta MFY</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>MFY nomi</th><th>Ko‘chalar</th><th>Amallar</th></tr></thead>
            <tbody>
            @foreach($mahallas as $mahalla)
                <tr class="{{ $selectedMahalla?->id === $mahalla->id ? 'selected-address-row' : '' }}">
                    <td>{{ $loop->iteration + ($mahallas->currentPage() - 1) * $mahallas->perPage() }}</td>
                    <td>{{ $mahalla->name }}</td>
                    <td>{{ $mahalla->streets_count }}</td>
                    <td>
                        <div class="address-actions">
                            <a class="secondary-button" href="{{ route('addresses.show', ['district' => $district, 'mahalla_id' => $mahalla->id]) }}">Ko‘chalarni ko‘rish</a>
                            <details class="inline-editor">
                                <summary class="ghost-button">Tahrirlash</summary>
                                <form method="POST" action="{{ route('mahallas.update', $mahalla) }}">
                                    @csrf
                                    @method('PUT')
                                    <input name="name" value="{{ $mahalla->name }}" required maxlength="255">
                                    <button class="primary-button" type="submit">Saqlash</button>
                                </form>
                            </details>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination-bar">
        <p>
            {{ $mahallas->firstItem() }}-{{ $mahallas->lastItem() }}
            / {{ $mahallas->total() }} ta MFY
        </p>

        @if($mahallas->hasPages())
            @php
                $paginationPages = collect([1, $mahallas->currentPage() - 2, $mahallas->currentPage() - 1, $mahallas->currentPage(), $mahallas->currentPage() + 1, $mahallas->currentPage() + 2, $mahallas->lastPage()])
                    ->filter(fn ($page) => $page >= 1 && $page <= $mahallas->lastPage())
                    ->unique()
                    ->sort()
                    ->values();
                $previousRenderedPage = null;
            @endphp
            <nav class="pagination-links" aria-label="MFY sahifalari">
                @if($mahallas->onFirstPage())
                    <span class="pagination-link disabled" aria-disabled="true">Oldingi</span>
                @else
                    <a class="pagination-link" href="{{ $mahallas->previousPageUrl() }}" rel="prev">Oldingi</a>
                @endif

                @foreach($paginationPages as $page)
                    @if($previousRenderedPage !== null && $page > $previousRenderedPage + 1)
                        <span class="pagination-gap" aria-hidden="true">...</span>
                    @endif

                    @if($page === $mahallas->currentPage())
                        <span class="pagination-link active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pagination-link" href="{{ $mahallas->url($page) }}">{{ $page }}</a>
                    @endif

                    @php($previousRenderedPage = $page)
                @endforeach

                @if($mahallas->hasMorePages())
                    <a class="pagination-link" href="{{ $mahallas->nextPageUrl() }}" rel="next">Keyingi</a>
                @else
                    <span class="pagination-link disabled" aria-disabled="true">Keyingi</span>
                @endif
            </nav>
        @endif
    </div>
</section>

@if($selectedMahalla)
    <section class="panel street-management" id="streets">
        <div class="panel-heading">
            <div>
                <p class="eyebrow">Tanlangan MFY</p>
                <h2>{{ $selectedMahalla->name }} ko‘chalari</h2>
            </div>
            <span class="muted-text">{{ $selectedMahalla->streets->count() }} ta ko‘cha</span>
        </div>

        <details class="address-editor" open>
            <summary>Yangi ko‘cha qo‘shish</summary>
            <form method="POST" action="{{ route('streets.store') }}" class="form-grid three">
                @csrf
                <input type="hidden" name="district_id" value="{{ $district->id }}">
                <input type="hidden" name="mahalla_id" value="{{ $selectedMahalla->id }}">
                <label>Ko‘cha nomi
                    <input name="name" required maxlength="255" placeholder="Masalan: Navoiy">
                </label>
                <label>Ko‘cha turi
                    <select name="type" required>
                        @foreach($streetTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </label>
                <div class="address-form-action"><button class="primary-button" type="submit">Ko‘cha qo‘shish</button></div>
            </form>
        </details>

        <div class="table-wrap">
            <table>
                <thead><tr><th>#</th><th>Ko‘cha nomi</th><th>Turi</th><th>Amal</th></tr></thead>
                <tbody>
                @forelse($selectedMahalla->streets as $street)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $street->name }}</td>
                        <td>{{ $streetTypes[$street->type] ?? $street->type }}</td>
                        <td>
                            <details class="inline-editor">
                                <summary class="ghost-button">Tahrirlash</summary>
                                <form method="POST" action="{{ route('streets.update', $street) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="mahalla_id" value="{{ $selectedMahalla->id }}">
                                    <input name="name" value="{{ $street->name }}" required maxlength="255">
                                    <select name="type" required>
                                        @foreach($streetTypes as $key => $label)<option value="{{ $key }}" @selected($street->type === $key)>{{ $label }}</option>@endforeach
                                    </select>
                                    <button class="primary-button" type="submit">Saqlash</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="muted-text">Bu MFYga hali ko‘cha qo‘shilmagan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@else
    <section class="panel empty-address-selection">
        <strong>Ko‘chalarni ko‘rish uchun MFYni tanlang.</strong>
        <p class="muted-text">Yuqoridagi ro‘yxatdan “Ko‘chalarni ko‘rish” tugmasini bosing.</p>
    </section>
@endif
@endsection
