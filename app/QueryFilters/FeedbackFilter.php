<?php

namespace App\QueryFilters;

class FeedbackFilter extends QueryFilter
{
    protected function filterableFields(): array
    {
        return ['type', 'date'];
    }

    protected function type($value): void
    {
        $this->builder->where('feedback.type', $value);
    }

    protected function date($value): void
    {
        $this->builder->whereDate('feedback.created_at', $value);
    }
}