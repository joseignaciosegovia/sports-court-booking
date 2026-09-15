<?php

namespace App\View\Components\Filters;

use Illuminate\View\Component;

class FilterBar extends Component
{
    public function __construct(
        public string $action,
        public array $activeFilters = [],
    ) {}

    public function render()
    {
        return view('components.filters.filter-bar');
    }
}