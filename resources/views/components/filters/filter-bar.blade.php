<div class="filters-card mb-3">
    <form method="GET" action="{{ $action }}" class="row g-2 align-items-center">
        {{ $slot }}
        <div class="col-12 col-md-auto">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4 flex-fill flex-md-grow-0">Filtrar</button>
                <a href="{{ $action }}" class="btn btn-outline-secondary px-3 flex-fill flex-md-grow-0">Limpiar</a>
            </div>
        </div>
    </form>

    @if(array_filter($activeFilters))
        <div class="d-flex flex-wrap gap-2 mt-3">
            {{ $chips ?? '' }}
        </div>
    @endif
</div>