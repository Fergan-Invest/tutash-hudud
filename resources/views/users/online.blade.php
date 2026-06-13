@extends('layouts.app')

@section('title', 'Online foydalanuvchilar')
@section('breadcrumb', 'Online foydalanuvchilar')

@section('content')
<section class="page-title compact-title">
    <div>
        <h1>Online foydalanuvchilar</h1>
        <p>Oxirgi 5 daqiqada tizimda faol bo'lgan foydalanuvchilar.</p>
    </div>
</section>

@if($users->isEmpty())
    <section class="empty-state-card" aria-label="Online foydalanuvchi yo'q">
        <div class="empty-illustration">
            <svg viewBox="0 0 96 96" aria-hidden="true">
                <circle cx="48" cy="34" r="16"/>
                <path d="M22 78c5-18 47-18 52 0"/>
                <path d="M70 24h14M77 17v14"/>
            </svg>
        </div>
        <h2>Hozircha online foydalanuvchi yo'q</h2>
        <p>Foydalanuvchi tizimga kirganda bu yerda ko'rinadi.</p>
    </section>
@else
    <section class="registry-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Foydalanuvchi</th>
                        <th>Rol</th>
                        <th>Hudud</th>
                        <th>Oxirgi faollik</th>
                        <th>IP manzil</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong>
                                <small>{{ $user->email }}</small>
                            </td>
                            <td><span class="status approved">{{ str_replace('_', ' ', $user->role) }}</span></td>
                            <td>{{ $user->district?->name ?? 'Barcha hududlar' }}</td>
                            <td>{{ $user->last_seen_at?->format('d.m.Y H:i:s') }}</td>
                            <td>{{ $user->last_ip ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection
