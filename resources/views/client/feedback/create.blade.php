@extends('layouts.client')

@section('title', 'Feedback · Moral de Calatrava')

@section('titleHeader', 'Feedback · Moral de Calatrava')

@push('styles')
    @vite([
        'resources/css/form.css',
        'resources/css/table.css'
    ])
@endpush

@section('client-content')
    <main class="main">
        {{-- BIENVENIDA --}}
        <div class="welcome-bar">
            <div class="welcome-avatar">{{ $authUser->initials }}</div>
            <div class="welcome-text">
                <h1>Bienvenida/o, {{ $authUser->name }}</h1>
                <p>Hoy es {{ $todayLong }}</p>
            </div>
            <span class="badge badge-green">
                <i class="ti ti-circle-check" aria-hidden="true"></i> Sesión activa
            </span>
        </div>
        <div class="card shadow-sm border-0">
            <form method="POST" action="{{ route('client.feedback.store') }}" class="needs-validation" name="feedback" enctype="multipart/form-data" novalidate>
                @csrf
                <div class="p-3 py-4">
                    <div class="seccionSubtitulo">
                        <i class="ti ti-mail"></i>
                        <div>
                            <h2>Enviar sugerencias e incidencias</h2>
                            <small class="text-muted">Realiza una sugerencia o envía una incidencia</small>
                        </div>
                    </div>
                    <hr class="mt-0 mb-4" style="border-color: #dee2e6;">

                    <div>
                        <div>
                            <label for="content" class="labels">Sugerencia o incidencia</label>
                            <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" placeholder="Describe el problema o tu propuesta con el máximo detalle posible" required></textarea>
                            <div class="invalid-feedback">
                                {{ $errors->first('content') ?: 'Introduce el texto de la sugerencia/incidencia.' }}
                            </div>
                            <br>
                            
                            <label for="type" class="labels">¿Es una sugerencia o una incidencia?</label>
                            <select class="form-select" name="type" id="type">
                                <option value="{{ \App\Enums\FeedbackType::Suggestion->value }}">{{ \App\Enums\FeedbackType::Suggestion->label() }}</option>
                                <option value="{{ \App\Enums\FeedbackType::Incident->value }}">{{ \App\Enums\FeedbackType::Incident->label() }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 d-flex justify-content-end">
                        <button class="btn btn-success px-4" type="submit" name="Enviar">Enviar</button>
                    </div>
                </div>
            </form>
        </div>
    </main>
@endsection

@push('scripts')
    @vite('resources/js/validation.js')
@endpush