<?php

namespace App\QueryFilters;

class CourtFilter extends QueryFilter
{
    protected function filterableFields(): array
    {
        return ['name', 'location'];
    }

    protected function name($value): void
    {
        $this->builder->where('courts.name', $value);
    }

    protected function location($value): void
    {
        $this->builder->where('courts.location', $value);
    }
}