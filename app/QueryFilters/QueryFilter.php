<?php

namespace App\QueryFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class QueryFilter
{
    protected Builder $builder;

    public function __construct(protected Request $request)
    {
    }

    /**
     * Aplica todos los filtros presentes en el request
     * que tengan un método correspondiente en la clase hija.
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->request->only($this->filterableFields()) as $name => $value) {
            if ($this->isEmpty($value)) {
                continue;
            }

            if (method_exists($this, $name)) {
                $this->$name($value);
            }
        }

        return $this->builder;
    }

    /**
     * Campos que el filtro puede procesar (cada uno tendrá sus campos específicos)
     * 
     */
    abstract protected function filterableFields(): array;

    protected function isEmpty($value): bool
    {
        return $value === null || $value === '';
    }
}