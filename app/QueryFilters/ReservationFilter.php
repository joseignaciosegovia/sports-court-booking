<?php

namespace App\QueryFilters;

class ReservationFilter extends QueryFilter
{
    protected function filterableFields(): array
    {
        return [
            'court_id', 
            'location', 
            'status', 
            'date', 
            'date_range', 
            'canceled_by'
        ];
    }

    protected function court_id($value): void
    {
        $this->builder->where('reservations.court_id', $value);
    }

    protected function location($value): void
    {
        $this->builder->whereHas('court', function ($query) use ($value) {
            $query->where('courts.location', $value);
        });
    }

    protected function status($value): void
    {
        $this->builder->where('reservations.payment_status', $value);
    }

    protected function date($value): void
    {
        $this->builder->whereDate('reservations.start_time', $value);
    }

    protected function date_range($value): void
    {
        if ($value === 'past') {
            $this->builder->where('reservations.start_time', '<', now());
        } elseif ($value === 'future') {
            $this->builder->where('reservations.start_time', '>=', now());
        }
    }

    protected function canceled_by($value): void
    {
        $this->builder->where('reservations.canceled_by', $value);
    }
}