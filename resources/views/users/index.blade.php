@extends('layouts.app')

@section('title', 'Foydalanuvchilar')
@section('breadcrumb', 'Foydalanuvchilarni boshqarish')

@section('content')
<section class="page-title compact-title">
    <div>
        <h1>Foydalanuvchilar</h1>
        <p>Parollarni yangilang yoki hisoblarni ma'lumotlarini o'chirmasdan vaqtincha o'chiring.</p>
    </div>
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
