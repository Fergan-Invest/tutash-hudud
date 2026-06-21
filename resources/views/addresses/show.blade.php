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
    {{ $mahallas->links() }}
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
