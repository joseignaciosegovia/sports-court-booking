<?php

namespace App\Models\Concerns;

use App\Helpers\SortHelper;
use Illuminate\Database\Eloquent\Builder;

trait Sortable
{
    /**
     * @param array $sortColumns  ['clave_url' => 'tabla.columna_bd']
     * @param string|null $defaultColumn
     * @param string $defaultDirection
     * @param array $joins  ['clave_url' => function (Builder $query) { ... }]
     *                      Se ejecuta solo si esa clave está presente en el orden solicitado.
     */
    public function scopeSort(
        Builder $query,
        array $sortColumns,
        ?string $defaultColumn = null,
        string $defaultDirection = 'desc',
        array $joins = []
    ): Builder {
        $sorts = SortHelper::getSorts($sortColumns);

        foreach (array_keys($sorts) as $column) {
            if (isset($joins[$column])) {
                $joins[$column]($query);
            }
        }

        foreach ($sorts as $column => $direction) {
            $query->orderBy($sortColumns[$column], $direction);
        }

        if (empty($sorts) && $defaultColumn) {
            $query->orderBy($defaultColumn, $defaultDirection);
        }

        return $query;
    }
}