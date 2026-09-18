<!DOCTYPE html>
<html lang="es">
    <head>
        {{-- Metadatos --}}
        <meta charset="UTF-8">
        <meta name="author" content="José Ignacio Segovia Ramírez">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        {{-- Titulo --}}
        <title>@yield('title', 'Reserva de pistas · Moral de Calatrava')</title>
        {{-- Favicon --}}
        <link rel="icon" type="image/x-icon" href="{{ asset('images/Moral.png') }}">
        {{-- Bootstrap --}}
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        {{-- Google Fonts --}}
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
        {{-- Iconos --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
        {{-- Hoja de estilos comunes a todas las vistas --}}
        @vite([
            'resources/css/app.css',
            'resources/css/body.css',
            'resources/css/header.css',
            'resources/css/footer.css',
        ])
        {{-- Hoja de estilos de cada vista --}}
        @stack('styles')
        {{-- Estilos de Livewire --}}
        @livewireStyles
        {{-- Animanate CSS --}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    </head>
    <body>
        <header>
            <div class="container-fluid" id="cabecera">
                <div class="cabecera-grid">
                    {{-- Logo --}}
                    <div class="cabecera-logo">
                        <a href="{{ route('home') }}"><img src="{{ asset('images/Moral2.png') }}" alt="Logo de la página"></a>
                    </div>
                    {{-- Título --}}
                    <div class="cabecera-texto">
                        {{-- El título contiene un enlace a la página principal --}}
                        <div class="cabeceraTitulo">
                            <a class="text-decoration-none" href="{{ route('home') }}">@yield('titleHeader', 'Reserva de pistas · Moral de Calatrava')</a>
                        </div>
                        <div class="cabeceraSubtitulo">Polideportivo y Ciudad Deportiva</div>
                    </div>
                    {{-- Acceso a la intranet --}}
                    <div class="cabecera-acciones">
                        <button class="btn btn-primary btn-intranet form-floating" onclick="window.location.href='{{ route('intranet.login') }}';">Intranet</button>
                        {{-- Botón de menú móvil --}}
                        @hasSection('menu')
                            <div class="col-auto">
                                <button id="btnMenu"><i class="ti ti-menu-2"></i></button>
                            </div>
                        @endif
                    </div>
                    
                    
                </div>
            </div>
        </header>
        {{-- Contenido de la página --}}
        @yield('menu')
        {{-- El segundo parámetro es en formato Volt para livewire/pages/auth --}}
        @yield('content', $slot ?? '')
        {{-- Se invocará un modal cada vez que recibamos success desde un controlador, que será cuando queramos invocar un modal informando de la operación realizada --}}
        @if (session('success'))
            <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">

                        <div class="modal-header">
                            <h5 class="modal-title" id="successModalLabel">
                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                ¡Operación completada!
                            </h5>

                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Cerrar">
                            </button>
                        </div>

                        <div class="modal-body">
                            {{ session('success') }}
                        </div>

                        <div class="modal-footer">
                            <button type="button"
                                    class="btn btn-primary"
                                    data-bs-dismiss="modal">
                                Aceptar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        {{-- Pie de página --}}
        <footer>
            <div class="container-fluid">
                <div class="row">
                    <div class="col footer-col" id="telefonos">
                        <strong>Teléfonos:</strong>
                        <ul class="nav flex-column">
                            <li class="nav-item mb-2">Ciudad deportiva: 656 539 016</li>
                            <li class="nav-item mb-2">Polideportivo: 926 319 495</li>
                        </ul>
                    </div>
                    <div class="col footer-col" id="correosElectronicos">
                        <strong>Correo electrónico:</strong>
                        <ul class="nav flex-column">
                            <li class="nav-item mb-2">Ciudad deportiva: Ciudaddeportivamoral@gmail.com</li>
                        </ul>
                    </div>
                    <div class="col footer-col" id="paginaWeb">
                        <strong>Página web:</strong>
                        <ul class="nav flex-column">
                            <li><a href="https://www.moraldecalatrava.org/web1/ciudad-deportiva-moral-de-calatrava/" target="_blank">Ciudad Deportiva</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
        {{-- JQuery --}}
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        {{-- Bootstrap --}}
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
        {{-- JavaScript común a todas las vistas --}}
        @vite('resources/js/layout.js')
        {{-- JavaScript específico de cada vista --}}
        @stack('scripts')
        {{-- Scripts de Livewire --}}
        @livewireScripts
    </body>
</html>