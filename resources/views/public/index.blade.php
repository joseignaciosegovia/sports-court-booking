@extends('layouts.app')

@push('scriptsCabecera')
    @vite('resources/css/public.css')
    @vite('resources/css/form.css')
    @vite('resources/css/responsive.css')
    @vite('resources/css/calendar.css')
@endpush

@section('content')
    <div class="container-fluid my-3 px-0">
        <div class="row g-0">
            <!-- Columna izquierda, Reservar pistas -->
            <div class="col-12 col-md-6 pt-4" id="informacionPrincipal">
                <div class="row px-3">
                    <h1>Reservar pistas en Moral de Calatrava</h1>
                    <hr class="mt-0 mb-4" style="border-color: #dee2e6;">
                    <p>Si te registras en nuestra web, podrás reservar horarios de las pistas deportivas de Moral de Calatrava.</p>
                    <p>Información sobre las pistas:</p>
                </div>
                <!-- Sección con los datos de las pistas -->
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
            </div>
            <div class="card shadow-sm border-0 col-12 col-md">
                <div class="card-header text-center">
                    <h2 class="d-flex justify-content-center">Registrarse</h2>
                    <a class="btn btn-secondary my-2 text-center w-auto" href="/login">Si ya tienes cuenta, inicia sesión aquí</a>
                </div>
                <div class="card-body" id="crearCuenta">
                    <form method="POST" class="row needs-validation px-4" action="{{ route('register') }}" name="crearUsuario" novalidate>
                        @csrf
                        <div class="row mt-3">
                            <div class="col-12 col-sm-6">
                                <label for="name" class="form-label">Nombre completo</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Nombre completo" autocomplete="off" required>
                                @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                                <label for="email" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="correo@ejemplo.com" autocomplete="off" required>
                                @error('email') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 col-sm-6">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Mínimo 8 caracteres" pattern=".{8,}" required>
                                @error('password') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                                <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Repite la contraseña" required>
                                @error('password_confirmation') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 col-sm-6">
                                <label for="dni" class="form-label">DNI</label>
                                <input type="text" class="form-control" id="dni" name="dni" placeholder="12345678A" autocomplete="off" pattern="[0-9]{8}[A-Z]" required>
                                @error('dni') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12 col-sm-6 mt-3 mt-sm-0">
                                <label for="phone" class="form-label">Teléfono (opcional)</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="600 000 000" autocomplete="off" pattern="[0-9]{9}">
                                @error('phone') <div class="text-danger small">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="mb-3">
                                <label for="photo" class="form-label">Foto de perfil (opcional)</label>
                                <input type="file" class="form-control" name="photo" id="photo">
                                @error('photo') <div class="text-danger small">{{ $message }}</div> @enderror
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
    <!-- Sección con las pistas y calendarios -->
    <div class="card shadow-sm border-0">
        <div class="p-3 py-4">
            <div id="consultarPistas" class="row column-gap-3">
                <h2 class="d-flex justify-content-center">Consultar pistas y sus horarios</h2>
                <!-- Div en el que irá el título de la pista -->
            <div class="col-12 accordion accordion-flush d-flex justify-content-center" id="elegirPista">
                @php
                    $counter = 0;
                @endphp
                @forelse($courtsByFacility as $courtsFacility)
                    @php
                        $facility = $courtsFacility->first()->location;
                    @endphp
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button 
                                class="accordion-button collapsed" 
                                type="button" data-bs-toggle="collapse" 
                                data-bs-target="#flush-collapse{{ $loop->index }}" 
                                aria-expanded="false" 
                                aria-controls="flush-collapse{{ $loop->index }}"
                            >
                                {{ $facility }}
                            </button>
                        </h2>
                        <div id="flush-collapse{{ $loop->index }}" class="accordion-collapse collapse"  data-bs-parent="#elegirPista">

                        <!-- Recorremos las pistas de la instalación -->
                        @forelse($courtsFacility as $court)
                            <div class="accordion-body">
                                <a class="nav-link court-link ms-3 my-1" data-court-id="{{ $court->id }}" data-court-name="{{ $court->name }}">{{ $court->name }}</a>
                            </div>
                        @empty
                            <p>No hay pistas disponibles en esta instalación</p>
                        @endforelse
                    </div>
                    @php
                        $counter++;
                    @endphp
                @empty
                    <p>No hay instalaciones disponibles</p>
                @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
{{-- Bloque del calendario, oculto hasta que se elija una pista --}}
<div class="card shadow-sm border-0">
    <div class="p-3 py-4">
        <div id="calendar-section" class="mt-4 d-none">
            <h2 class="d-flex justify-content-center mb-3">
                Horarios de la pista <span id="selected-court-name" class="ms-2 fw-bold"></span>
            </h2>
            {{-- Aquí se mostrará el calendario --}}
            <div id="calendar"></div>
        </div>
    </div>
</div>

@endsection

@push('scriptsPie')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendarSection = document.getElementById('calendar-section');
            const calendarEl = document.getElementById('calendar');
            const selectedCourtName = document.getElementById('selected-court-name');

            let calendar = null;

            function initCalendar(courtId) {
                // Si ya había un calendario (porque habíamos pinchado previamente en una pista) lo destruimos para crear el nuevo
                if (calendar) {
                    calendar.destroy();
                }

                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'timeGridWeek',
                    locale: 'es',
                    initialView: 'timeGridWeek',
                    slotMinTime: '{{ $openingTime }}:00',
                    slotMaxTime: '{{ $closingTime }}:00',
                    slotDuration: '01:00:00',
                    hiddenDays: [6, 0],
                    height: 'auto',
                    allDaySlot: false,
                    selectable: true,
                    selectOverlap: false,
                    // Accedemos a la ruta que invocará (en web.php) el controlador que devolverá los horarios ocupados de esta pista
                    events: `/horarios/${courtId}`,

                // Formato de la columna que indica la hora
                slotLabelFormat:{
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true,
                    meridiem: 'short',
                },

                headerToolbar: {
                    left: "prev,next,today",
                    center: "title",
                    right: "timeGridWeek,timeGridDay"
                }, 
                });

                calendar.render();
            }

            // Si pinchamos en una pista (dentro de los acordeones)
            document.querySelectorAll('.court-link').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();

                    const courtId = this.dataset.courtId;
                    const courtName = this.dataset.courtName;

                    // Resalta visualmente la pista elegida
                    document.querySelectorAll('.court-link').forEach(l => l.classList.remove('fw-bold', 'text-primary'));
                    this.classList.add('fw-bold', 'text-primary');

                    selectedCourtName.textContent = courtName;
                    calendarSection.classList.remove('d-none');

                    initCalendar(courtId);

                    // Scroll suave hasta el calendario, útil sobre todo en móvil
                    calendarSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/locales/es.global.min.js"></script>
@endpush

