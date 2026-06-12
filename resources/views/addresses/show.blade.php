@extends('layouts.app')

@section('title', $district->name)
@section('breadcrumb', $district->name)

@section('content')
<section class="page-title">
    <div>
        <p class="eyebrow">Tuman manzillari</p>
        <h1>{{ $district->name }}</h1>
        <p>District ID: {{ $district->external_id }}</p>
    </div>
    <a class="secondary-button" href="{{ route('addresses.index') }}">Orqaga</a>
</section>

<section class="metrics address-summary">
    <article class="metric-card"><span>MFYlar</span><strong>{{ $district->mahallas_count }}</strong><small>{{ $district->name }} bo‘yicha</small></article>
    <article class="metric-card"><span>Ko‘chalar</span><strong>{{ $district->streets_count }}</strong><small>Tizimga qo‘shilgan</small></article>
</section>

<section class="panel table-panel">
    <div class="panel-heading">
        <h2>MFYlar ro‘yxati</h2>
        <span class="muted-text">{{ $mahallas->total() }} ta MFY</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>MFY nomi</th><th>Ko‘chalar soni</th></tr></thead>
            <tbody>
            @foreach($mahallas as $mahalla)
                <tr>
                    <td>{{ $loop->iteration + ($mahallas->currentPage() - 1) * $mahallas->perPage() }}</td>
                    <td>{{ $mahalla->name }}</td>
                    <td>{{ $mahalla->streets_count }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $mahallas->links() }}
</section>
@endsection
