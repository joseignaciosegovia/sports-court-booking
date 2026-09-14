<?php

namespace App\Helpers;

use Illuminate\Support\HtmlString;

class SortHelper
{
    /**
     * Estados de ordenación permitidos.
     */
    private const DIRECTIONS = [
        'asc',
        'desc',
    ];

    /**
     * Devuelve las ordenaciones válidas recibidas en la petición.
     *
     * $allowedColumns puede ser:
     *
     * [
     *     'court',
     *     'start_time',
     *     'price',
     *     'status',
     * ]
     *
     * o:
     *
     * [
     *     'court' => 'courts.name',
     *     'start_time' => 'reservations.start_time',
     *     'price' => 'courts.reservation_price',
     *     'status' => 'reservations.payment_status',
     * ]
     *
     * El helper no necesita conocer las columnas reales de la BD.
     * Solo valida las claves permitidas.
     */
    public static function getSorts(array $allowedColumns): array
    {
        $sorts = request()->input('sort', []);

        if (!is_array($sorts)) {
            return [];
        }

        $allowedColumns = array_keys(
            self::normalizeColumns($allowedColumns)
        );

        return collect($sorts)
            ->filter(
                fn ($direction, $column) =>
                    in_array($column, $allowedColumns, true)
                    && in_array($direction, self::DIRECTIONS, true)
            )
            ->toArray();
    }

    /**
     * Genera la URL para cambiar el estado de ordenación
     * de una columna.
     *
     * Ciclo:
     *
     * sin orden → asc → desc → sin orden
     */
    public static function url(string $column, array $allowedColumns = []): string 
    {
        $sorts = self::getSorts($allowedColumns);

        $current = $sorts[$column] ?? null;

        $next = match ($current) {
            null => 'asc',
            'asc' => 'desc',
            'desc' => null,
        };

        $query = request()->query();

        // Al cambiar la ordenación volvemos a la primera página.
        unset($query['page']);

        if ($next === null) {
            unset($query['sort'][$column]);

            if (empty($query['sort'])) {
                unset($query['sort']);
            }
        } else {
            $query['sort'][$column] = $next;
        }

        return request()->url()
            . (!empty($query)
                ? '?' . http_build_query($query)
                : '');
    }

    /**
     * Genera el icono de ordenación de una columna.
     *
     * Si hay varias columnas ordenadas, muestra además
     * la prioridad:
     *
     * Pista ↑ 1
     * Fecha ↓ 2
     * Precio ↑ 3
     */
    public static function icon(
        string $column,
        array $allowedColumns = []
    ): HtmlString {
        $sorts = self::getSorts($allowedColumns);

        $direction = $sorts[$column] ?? null;

        $priority = null;

        if ($direction !== null) {
            $orderedColumns = array_keys($sorts);

            $position = array_search(
                $column,
                $orderedColumns,
                true
            );

            if ($position !== false) {
                $priority = $position + 1;
            }
        }

        $icon = match ($direction) {
            'asc' => 'ti-arrow-up',
            'desc' => 'ti-arrow-down',
            default => 'ti-arrows-sort',
        };

        $html = '<span class="sort-indicator">';

        $html .= '<i class="ti '
            . e($icon)
            . '" aria-hidden="true"></i>';

        if ($priority !== null) {
            $html .= '<sup class="sort-priority">'
                . e($priority)
                . '</sup>';
        }

        $html .= '</span>';

        return new HtmlString($html);
    }

    /**
     * Normaliza la definición de columnas.
     *
     * Permite utilizar tanto:
     *
     * ['name', 'email']
     *
     * como:
     *
     * [
     *     'name' => 'users.name',
     *     'email' => 'users.email',
     * ]
     */
    private static function normalizeColumns(array $columns): array
    {
        $normalized = [];

        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                $normalized[$value] = $value;
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
