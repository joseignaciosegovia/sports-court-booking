<div class="filters-card mb-3">
    <form method="GET" action="{{ $action }}" class="row g-2 align-items-center">
        {{ $slot }}
        <div class="col-md-auto">
            <button type="submit" class="btn btn-primary px-4">Filtrar</button>
        </div>
        <div class="col-md-auto">
            <a href="{{ $action }}" class="btn btn-outline-secondary px-3">Limpiar</a>
        </div>
    </form>

    @if(array_filter($activeFilters))
        <div class="d-flex flex-wrap gap-2 mt-3">
            {{ $chips ?? '' }}
        </div>
    @endif
</div>