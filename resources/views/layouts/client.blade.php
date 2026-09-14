@extends('layouts.app')

@section('menu')
    {{-- Sección para que aparezca el botón del menú --}}
    <div class="overlay" id="overlay" onclick="desplegarMenu()"></div>
@endsection

@push('scriptsCabecera')
    @vite('resources/css/navbar.css')
    @vite('resources/css/subtitle.css')
    @vite('resources/css/welcome.css')
    @vite('resources/css/responsive.css')
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
                    <small>Usuario activo</small>
                </div>
            </div>
            <div class="nav-section">General</div>
                <a class="nav-item {{ request()->routeIs('client.dashboard') ? 'active' : '' }}" href="{{ route('client.dashboard') }}">
                    <i class="ti ti-home" aria-hidden="true"></i>
                    Inicio
                </a>
            <a class="nav-item {{ request()->routeIs('client.profile.*') ? 'active' : '' }}" href="{{ route('client.profile.edit') }}">
                <i class="ti ti-user" aria-hidden="true"></i>
                Datos personales
            </a>
            <div class="nav-section">Reservas</div>
                <a class="nav-item {{ request()->routeIs('client.reservations.index') ? 'active' : '' }}" href="{{ route('client.reservations.index') }}">
                    <i class="ti ti-calendar" aria-hidden="true"></i>
                    Historial de reservas
                </a>
                <a class="nav-item {{ request()->routeIs('client.reservations.create') ? 'active' : '' }}" href="{{ route('client.reservations.create') }}">
                    <i class="ti ti-plus" aria-hidden="true"></i>
                    Nueva reserva
                </a>
            <div class="nav-section">Soporte</div>
                <a class="nav-item {{ request()->routeIs('client.suggestions.*') ? 'active' : '' }}" href="{{ route('client.suggestions.index') }}">
                    <i class="ti ti-mail" aria-hidden="true"></i>
                    Buzón de incidencias
                </a>

            <form method="POST" action="{{ route('logout') }}" style="margin-top: auto;">
                @csrf
                <button type="submit" class="nav-item">
                    <i class="ti ti-logout" aria-hidden="true"></i>
                    Cerrar sesión
                </button>
            </form>
        </nav>

        @yield('client-content')
    </div>
@endsection