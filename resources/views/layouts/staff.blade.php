@extends('layouts.app')

@section('menu')
    {{-- Sección que hay que crear para que aparezca el botón del menú --}}
    <div class="overlay" id="overlay" onclick="desplegarMenu()"></div>
@endsection

@section('title')
    Gestión de pistas · Moral de Calatrava
@endsection

@push('styles')
    @vite([
        'resources/css/navbar.css',
        'resources/css/subtitle.css',
        'resources/css/welcome.css',
        'resources/css/responsive.css'
    ])
@endpush

@section('content')
    <div class="layout" id="seccionPrincipal">
        <nav class="sidebar" aria-label="Menú principal">
            <div class="nav-usuario">
            {{-- Mostramos el nombre y la foto del usuario autenticado --}}
                <div class="avatar">
                    <img
                        class="rounded-circle"
                        src="{{ $authUser->photo_url }}"
                        alt="Foto de perfil"
                        width="60"
                        height="60"
                        style="object-fit: cover;"
                    >
                </div>
                <div>
                    <span>{{ $authUser->name }}</span>
                    <small>{{ $authUser->role === 'admin' ? 'Admin activo' : 'Mánager activo' }}</small>
                </div>
            </div>
            <div class="nav-section">General</div>
                <a class="nav-item {{ request()->routeIs('manager.dashboard') ? 'active' : '' }}"
                   href="{{ route('manager.dashboard') }}">
                    <i class="ti ti-home" aria-hidden="true"></i>
                    Inicio
                </a>
                <a class="nav-item {{ request()->routeIs('manager.profile.*') || request()->routeIs('admin.profile.*') ? 'active' : '' }}"
                   href="{{ $authUser->role === 'admin' ? route('admin.profile.edit') : route('manager.profile.edit') }}">
                    <i class="ti ti-user" aria-hidden="true"></i>
                    Datos personales
                </a>

            @if($authUser->role === 'admin')
                <div class="nav-section">Administración</div>
                    <a class="nav-item {{ request()->routeIs('admin.managers.*') ? 'active' : '' }}" href="{{ route('admin.managers.index') }}">
                        <i class="ti ti-user-cog" aria-hidden="true"></i>
                        Administrar gestores
                    </a>
            @endif

            <div class="nav-section">Reservas</div>
                <a class="nav-item {{ request()->routeIs('manager.reservations.index') ? 'active' : '' }}" href="{{ route('manager.reservations.index') }}">
                    <i class="ti ti-calendar" aria-hidden="true"></i>
                    Historial de reservas
                </a>
                <a class="nav-item {{ request()->routeIs('manager.reservations.cancellations') ? 'active' : '' }}" href="{{ route('manager.reservations.cancellations') }}">
                    <i class="ti ti-calendar-x" aria-hidden="true"></i>
                    Reservas canceladas
                </a>
                <a class="nav-item {{ request()->routeIs('manager.reservations.create') ? 'active' : '' }}" href="{{ route('manager.reservations.create') }}">
                    <i class="ti ti-plus" aria-hidden="true"></i>
                    Nueva reserva
                </a>
            <div class="nav-section">Pistas</div>
                <a class="nav-item {{ request()->routeIs('manager.courts.*') ? 'active' : '' }}" href="{{ route('manager.courts.index') }}">
                    <i class="ti ti-soccer-field" aria-hidden="true"></i>
                    Pistas
                </a>
            <div class="nav-section">Consultas</div>
                <a class="nav-item {{ request()->routeIs('manager.feedback.index') ? 'active' : '' }}" href="{{ route('manager.feedback.index') }}">
                    <i class="ti ti-mail" aria-hidden="true"></i>
                    Buzón de incidencias de los clientes
                </a>

            <form method="POST" action="{{ route('logout') }}" style="margin-top: auto;">
                @csrf
                <button type="submit" class="nav-item">
                    <i class="ti ti-logout" aria-hidden="true"></i>
                    Cerrar sesión
                </button>
            </form>
        </nav>

        @yield('manager-content')
    </div>
@endsection