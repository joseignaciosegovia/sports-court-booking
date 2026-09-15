<?php

namespace App\View\Components\Filters;

use Illuminate\View\Component;

class FilterChip extends Component
{
    public function __construct(
        public string $label,
        public string $removeUrl,
    ) {}

    public function render()
    {
        return view('components.filters.filter-chip');
    }
}