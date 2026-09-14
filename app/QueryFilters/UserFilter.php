<?php

namespace App\QueryFilters;

class UserFilter extends QueryFilter
{
    protected function filterableFields(): array
    {
        return ['email', 'name', 'dni', 'phone', 'role'];
    }

    protected function email($value): void
    {
        $this->builder->where('users.email', $value);
    }

    protected function name($value): void
    {
        $this->builder->where('users.name', $value);
    }

    protected function dni($value): void
    {
        $this->builder->where('users.dni', $value);
    }

    protected function phone($value): void
    {
        $this->builder->where('users.phone', $value);
    }

    protected function role($value): void
    {
        $this->builder->where('users.role', $value);
    }
}