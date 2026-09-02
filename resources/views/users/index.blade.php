@extends('layouts.app')

@section('title', 'Foydalanuvchilar')
@section('breadcrumb', 'Foydalanuvchilarni boshqarish')

@section('content')
<section class="page-title compact-title">
    <div>
        <h1>Foydalanuvchilar</h1>
        <p>Yangi hisob qo'shing, parollarni yangilang yoki hisoblarni vaqtincha o'chiring.</p>
    </div>
</section>

<section class="registry-card user-create-card">
    <div class="section-heading">
        <div><h2>Yangi foydalanuvchi</h2><p>Foydalanuvchi o'zi kiritgan arizalar uchun javobgar bo'ladi.</p></div>
    </div>
    <form method="POST" action="{{ route('users.store') }}" class="form-grid user-create-form">
        @csrf
        <label>F.I.Sh.
            <input name="name" value="{{ old('name') }}" maxlength="255" required>
            @error('name')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label>Login (email)
            <input name="email" type="email" value="{{ old('email') }}" maxlength="255" required>
            @error('email')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label>Rol
            <select name="role" id="new-user-role" required>
                <option value="tuman" @selected(old('role', 'tuman') === 'tuman')>Tuman operatori</option>
                <option value="invest" @selected(old('role') === 'invest')>Invest operatori</option>
                <option value="viloyat_hokimi" @selected(old('role') === 'viloyat_hokimi')>Viloyat hokimi (faqat ko'rish)</option>
            </select>
            @error('role')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label>Tuman
            <select name="district_id" id="new-user-district">
                <option value="">Tumanni tanlang</option>
                @foreach($districts as $district)
                    <option value="{{ $district->id }}" @selected((string) old('district_id') === (string) $district->id)>{{ $district->name }}</option>
                @endforeach
            </select>
            @error('district_id')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label>Parol
            <span class="password-field">
                <input id="new-user-password" name="password" type="password" minlength="8" required>
                <button class="password-toggle" type="button" data-password-toggle aria-label="Parolni ko‘rsatish" aria-controls="new-user-password" aria-pressed="false">
                    <svg class="eye-open" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="eye-closed" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18M10.6 10.7a2 2 0 0 0 2.7 2.7M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a17 17 0 0 1-2.1 3.2M6.6 6.6C3.7 8.5 2 12 2 12s3.5 8 10 8a9.8 9.8 0 0 0 4.1-.9"/></svg>
                </button>
            </span>
            @error('password')<small class="field-error">{{ $message }}</small>@enderror
        </label>
        <label>Parolni takrorlang
            <span class="password-field">
                <input id="new-user-password-confirmation" name="password_confirmation" type="password" minlength="8" required>
                <button class="password-toggle" type="button" data-password-toggle aria-label="Parolni ko‘rsatish" aria-controls="new-user-password-confirmation" aria-pressed="false">
                    <svg class="eye-open" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="eye-closed" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 3 18 18M10.6 10.7a2 2 0 0 0 2.7 2.7M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a17 17 0 0 1-2.1 3.2M6.6 6.6C3.7 8.5 2 12 2 12s3.5 8 10 8a9.8 9.8 0 0 0 4.1-.9"/></svg>
                </button>
            </span>
        </label>
        <div class="user-create-action"><button class="primary-button" type="submit">Foydalanuvchi qo'shish</button></div>
    </form>
</section>

<section class="registry-card user-management-card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Foydalanuvchi</th>
                    <th>Rol va hudud</th>
                    <th>Holat</th>
                    <th>Parolni o'zgartirish</th>
                    <th>Hisob amali</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></td>
                        <td>{{ str_replace('_', ' ', $user->role) }}<small>{{ $user->district?->name ?? 'Barcha hududlar' }}</small></td>
                        <td><span class="status {{ $user->is_active ? 'approved' : 'rejected' }}">{{ $user->is_active ? 'Faol' : 'O‘chirilgan' }}</span></td>
                        @if(auth()->user()->is($user))
                            <td colspan="2"><small>Joriy Invest hisobi bu sahifadan o'zgartirilmaydi.</small></td>
                        @else
                            <td>
                                <form class="user-password-form" method="POST" action="{{ route('users.password.update', $user) }}">
                                    @csrf @method('PATCH')
                                    <input name="password" type="password" minlength="8" placeholder="Yangi parol" required>
                                    <input name="password_confirmation" type="password" minlength="8" placeholder="Parolni takrorlang" required>
                                    <button class="secondary-button" type="submit">Yangilash</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('users.status.update', $user) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                    <button class="{{ $user->is_active ? 'danger-button' : 'secondary-button' }}" type="submit">
                                        {{ $user->is_active ? 'O‘chirish' : 'Faollashtirish' }}
                                    </button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection
