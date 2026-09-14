<?php

namespace App\QueryFilters;

class SuggestionFilter extends QueryFilter
{
    protected function filterableFields(): array
    {
        return ['type', 'date'];
    }

    protected function type($value): void
    {
        $this->builder->where('suggestions_incidents.type', $value);
    }

    protected function date($value): void
    {
        $this->builder->whereDate('suggestions_incidents.created_at', $value);
    }
}