@extends('layouts.app')

@push('styles')
    @vite([
        'resources/css/public.css',
        'resources/css/subtitle.css',
        'resources/css/form.css',
        'resources/css/responsive.css',
        'resources/css/calendar.css'
    ])
@endpush

@section('content')
    <div class="container-fluid my-3 px-0">
        <div class="row g-0">
            {{-- Columna izquierda, Reservar pistas --}}
            <div class="col-12 col-md-6 pt-4" id="informacionPrincipal">
                <div class="row px-3">
                    <h1 class="text-center">Reservar pistas en Moral de Calatrava</h1>
                    <hr class="mt-0 mb-4" style="border-color: #dee2e6;">
                    <p>Si te registras en nuestra web, podrás reservar horarios de las pistas deportivas de Moral de Calatrava.</p>
                    <p>Información sobre las pistas:</p>
                </div>
                {{-- Sección con los datos de las pistas --}}
                <div class="datosPistas px-5">
                    <div class="datosPistasSeccion">
                        <div class="datosPistasTitulo">{{ $numberOfCourts }}</div>
                        <div class="datosPistasSubtitulo">Pistas disponibles</div>
                    </div>
                    <div class="datosPistasSeccion">
                        <div class="datosPistasTitulo">{{ $numberOfFacilities }}</div>
                        <div class="datosPistasSubtitulo">Instalaciones</div>
                    </div>
                    <div class="datosPistasSeccion">
                        <div class="datosPistasTitulo">{{ $openingTime }}</div>
                        <div class="datosPistasSubtitulo">Hora de apertura</div>
                    </div>
                    <div class="datosPistasSeccion">
                        <div class="datosPistasTitulo">{{ $closingTime }}</div>
                        <div class="datosPistasSubtitulo">Hora de cierre</div>
                    </div>
                </div>
                {{-- Botón para dirigirse al calendario --}}
                <div class="text-center py-5">
                    <a href="#consultarPistas" class="btn btn-success w-auto">Ver horarios de las pistas ↓</a>
                </div>
            </div>
            <div class="card shadow-sm border-0 col-12 col-md">
                <div class="card-header text-center">
                    <h2 class="d-flex justify-content-center">Registrarse</h2>
                    <a class="my-2 text-center w-auto" href="{{ route('login') }}"><u>¿Ya tienes cuenta? Inicia sesión aquí</u></a>
                </div>
                <div class="card-body" id="crearCuenta">
                    <form method="POST" class="row needs-validation px-4" action="{{ route('register') }}" name="crearUsuario" enctype="multipart/form-data" novalidate>
                        @csrf
                        <div class="row mt-3">
                            <div class="col-12 col-sm-6">
                                <label for="name" class="form-label">Nombre completo</label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Nombre completo" autocomplete="off" required>
                                <div class="invalid-feedback">
                                    {{ $errors->first('name') ?: 'Introduce tu nombre completo.' }}
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                                <label for="email" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="correo@ejemplo.com" autocomplete="off" required>
                                <div class="invalid-feedback">
                                    {{ $errors->first('email') ?: 'Introduce un correo electrónico válido.' }}
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 col-sm-6">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Mínimo 8 caracteres" pattern=".{8,}" required>
                                <div class="invalid-feedback">
                                    {{ $errors->first('password') ?: 'La contraseña debe tener al menos 8 caracteres.' }}
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                                <label for="password_confirmation" class="form-label @error('password_confirmation') is-invalid @enderror">Confirmar contraseña</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Repite la contraseña" required>
                                <div class="invalid-feedback">
                                    {{ $errors->first('password_confirmation') ?: 'Las contraseñas no coinciden.' }}
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 col-sm-6">
                                <label for="dni" class="form-label">DNI</label>
                                <input type="text" class="form-control @error('dni') is-invalid @enderror" id="dni" name="dni" placeholder="12345678A" autocomplete="off" pattern="[0-9]{8}[A-Za-z]" oninput="this.value = this.value.toUpperCase()" required>
                                <div class="invalid-feedback">
                                    {{ $errors->first('dni') ?: 'El DNI debe tener 8 dígitos seguidos de una letra válida.' }}
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                                <label for="phone" class="form-label">Teléfono (opcional)</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" placeholder="600 000 000" autocomplete="off" pattern="[0-9]{9}">
                                <div class="invalid-feedback">
                                    {{ $errors->first('phone') ?: 'El teléfono debe tener 9 dígitos.' }}
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="mb-3">
                                <label for="photo" class="form-label">Foto de perfil (opcional)</label>
                                <input type="file" class="form-control @error('photo') is-invalid @enderror" name="photo" id="photo">
                                <div class="invalid-feedback">
                                    {{ $errors->first('photo') ?: 'La imagen no es válida o supera el tamaño máximo permitido.' }}
                                </div>
                            </div>
                            <div class="mb-3 text-center">
                                <button type="submit" class="btn btn-success w-auto" id="btCrearUsuario" name="crear">Crear Usuario</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- Sección con las pistas y calendarios --}}
    <div class="card shadow-sm border-0">
        <div class="p-3 py-4">
            <div class="seccionSubtitulo" id="consultarPistas">
                <i class="ti ti-soccer-field"></i>
                <div>
                    <h2>Consultar pistas y sus horarios</h2>
                    <small class="text-muted">Elige una pista para ver sus horarios disponibles y ocupados</small>
                </div>
            </div>
            <div class="col-12 accordion accordion-flush mt-4" id="elegirPista">
                @forelse($courtsByFacility as $courtsFacility)
                    @php
                        $facility = $courtsFacility->first()->location;
                        $isFirst = $loop->first;
                    @endphp
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button 
                                class="accordion-button {{ $isFirst ? '' : 'collapsed' }}"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#flush-collapse{{ $loop->index }}"
                                aria-expanded="{{ $isFirst ? 'true' : 'false' }}"
                                aria-controls="flush-collapse{{ $loop->index }}"
                            >
                                <i class="ti {{ $facility === 'Polideportivo' ? 'ti-soccer-field' : 'ti-building-stadium' }} me-2" aria-hidden="true"></i>
                                {{ $facility }}
                            </button>
                        </h2>
                        <div id="flush-collapse{{ $loop->index }}" class="accordion-collapse collapse {{ $isFirst ? 'show' : '' }}"  data-bs-parent="#elegirPista">

                        {{-- Recorremos las pistas de la instalación --}}
                        <div class="accordion-body court-list">
                            @forelse($courtsFacility as $court)
                            
                                <a href="#" class="nav-link court-link"
                                    data-court-id="{{ $court->id }}"
                                    data-court-name="{{ $court->name }}"
                                >
                                    <span class="court-dot" aria-hidden="true"></span>
                                    {{ $court->name }}
                                </a>
                            
                            @empty
                                <p class="text-muted mb-0">No hay pistas disponibles en esta instalación</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                @empty
                    <p>No hay instalaciones disponibles</p>
                @endforelse
                </div>
            </div>
        </div>
    </div>
{{-- Bloque del calendario, oculto hasta que se elija una pista --}}
<div class="card shadow-sm border-0">
    <div class="p-3">
        <div id="calendar-section" class="mt-4 d-none"
            data-opening-time="{{ $openingTime }}"
            data-closing-time="{{ $closingTime }}"
            data-schedule-url-template="{{ route('public.courts.schedule', ['court' => 'COURT_ID']) }}"
        >
            {{-- Subtítulo indicando la pista elegida --}}
            <div class="seccionSubtitulo">
                <i class="ti ti-soccer-field"></i>
                <div>
                    <h2>Horarios de la pista<span id="selected-court-name" class="ms-2 fw-bold text-primary"></span></h2>
                    <small class="text-muted">Puedes ver los horarios disponibles y ocupados de la pista elegida</small>
                </div>
            </div>
            {{-- Leyenda --}}
            <div class="calendar-legend d-flex align-items-center mt-3 mb-3" role="group" aria-label="Leyenda del calendario">
                <span class="legend-title">Leyenda:</span>
                <span class="legend-item"><span class="legend-dot legend-disponible"></span>Libre</span>
                <span class="legend-item"><span class="legend-dot legend-ocupada"></span>Ocupada</span>
            </div>
            {{-- Aquí se mostrará el calendario --}}
            <div id="calendar"></div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/es.global.min.js"></script>
    @vite('resources/js/public-courts-calendar.js')
    @vite('resources/js/validation.js')
@endpush

