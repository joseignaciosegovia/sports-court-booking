<?php

namespace Tests\Feature\Helpers;

use App\Helpers\SortHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SortHelperTest extends TestCase
{
    use RefreshDatabase;

    private array $allowedColumns = [
        'court',
        'start_time',
        'price',
        'status',
    ];

    private array $mappedColumns = [
        'court' => 'courts.name',
        'start_time' => 'reservations.start_time',
        'price' => 'courts.reservation_price',
        'status' => 'reservations.payment_status',
    ];

    private function request(array $query = []): void
    {
        $this->app->instance(
            'request',
            Request::create(
                '/reservations',
                'GET',
                $query
            )
        );
    }

    // ─────────────────────────────────────────────
    // getSorts()
    // ─────────────────────────────────────────────

    public function test_devuelve_las_ordenaciones_validas(): void
    {
        $this->request([
            'sort' => [
                'court' => 'asc',
                'start_time' => 'desc',
            ],
        ]);

        $this->assertSame(
            [
                'court' => 'asc',
                'start_time' => 'desc',
            ],
            SortHelper::getSorts($this->allowedColumns)
        );
    }

    public function test_acepta_columnas_definidas_como_array_asociativo(): void
    {
        $this->request([
            'sort' => [
                'court' => 'asc',
                'price' => 'desc',
            ],
        ]);

        $this->assertSame(
            [
                'court' => 'asc',
                'price' => 'desc',
            ],
            SortHelper::getSorts($this->mappedColumns)
        );
    }

    public function test_ignora_columnas_no_permitidas(): void
    {
        $this->request([
            'sort' => [
                'court' => 'asc',
                'unknown' => 'desc',
            ],
        ]);

        $this->assertSame(
            [
                'court' => 'asc',
            ],
            SortHelper::getSorts($this->allowedColumns)
        );
    }

    public function test_ignora_direcciones_no_permitidas(): void
    {
        $this->request([
            'sort' => [
                'court' => 'asc',
                'start_time' => 'desc',
                'price' => 'invalid',
                'status' => 'ASC',
            ],
        ]);

        $this->assertSame(
            [
                'court' => 'asc',
                'start_time' => 'desc',
            ],
            SortHelper::getSorts($this->allowedColumns)
        );
    }

    public function test_devuelve_array_vacio_si_sort_no_es_un_array(): void
    {
        $this->request([
            'sort' => 'court',
        ]);

        $this->assertSame(
            [],
            SortHelper::getSorts($this->allowedColumns)
        );
    }

    public function test_devuelve_array_vacio_si_no_hay_sort(): void
    {
        $this->request();

        $this->assertSame(
            [],
            SortHelper::getSorts($this->allowedColumns)
        );
    }

    // ─────────────────────────────────────────────
    // url()
    // ─────────────────────────────────────────────

    public function test_url_sin_orden_crea_orden_ascendente(): void
    {
        $this->request([
            'filter' => 'active',
        ]);

        $url = SortHelper::url('court', $this->allowedColumns);

        $this->assertSame(
            'http://localhost/reservations?filter=active&sort%5Bcourt%5D=asc',
            $url
        );
    }

    public function test_url_con_ascendente_cambia_a_descendente(): void
    {
        $this->request([
            'sort' => [
                'court' => 'asc',
            ],
        ]);

        $url = SortHelper::url('court', $this->allowedColumns);

        $this->assertSame(
            'http://localhost/reservations?sort%5Bcourt%5D=desc',
            $url
        );
    }

    public function test_url_con_descendente_elimina_la_ordenacion(): void
    {
        $this->request([
            'sort' => [
                'court' => 'desc',
            ],
        ]);

        $url = SortHelper::url('court', $this->allowedColumns);

        $this->assertSame(
            'http://localhost/reservations',
            $url
        );
    }

    public function test_url_al_eliminar_una_ordenacion_conserva_las_demas(): void
    {
        $this->request([
            'sort' => [
                'court' => 'desc',
                'start_time' => 'asc',
            ],
        ]);

        $url = SortHelper::url('court', $this->allowedColumns);

        $this->assertSame(
            'http://localhost/reservations?sort%5Bstart_time%5D=asc',
            $url
        );
    }

    public function test_url_elimina_la_pagina_actual(): void
    {
        $this->request([
            'page' => 3,
            'sort' => [
                'court' => 'asc',
            ],
        ]);

        $url = SortHelper::url('court', $this->allowedColumns);

        $this->assertSame(
            'http://localhost/reservations?sort%5Bcourt%5D=desc',
            $url
        );

        $this->assertStringNotContainsString(
            'page=',
            $url
        );
    }

    public function test_url_conserva_otros_parametros_de_la_peticion(): void
    {
        $this->request([
            'search' => 'pista',
            'status' => 'paid',
            'page' => 2,
            'sort' => [
                'court' => 'asc',
            ],
        ]);

        $url = SortHelper::url('court', $this->allowedColumns);

        $this->assertSame(
            'http://localhost/reservations?search=pista&status=paid&sort%5Bcourt%5D=desc',
            $url
        );
    }

    public function test_url_puede_crear_ordenacion_en_una_columna_no_ordenada(): void
    {
        $this->request([
            'sort' => [
                'court' => 'asc',
            ],
        ]);

        $url = SortHelper::url('price', $this->allowedColumns);

        $this->assertSame(
            'http://localhost/reservations?sort%5Bcourt%5D=asc&sort%5Bprice%5D=asc',
            $url
        );
    }

    // ─────────────────────────────────────────────
    // icon()
    // ─────────────────────────────────────────────

    public function test_icono_sin_orden_muestra_icono_por_defecto(): void
    {
        $this->request();

        $html = SortHelper::icon(
            'court',
            $this->allowedColumns
        )->toHtml();

        $this->assertStringContainsString(
            'ti-arrows-sort',
            $html
        );

        $this->assertStringNotContainsString(
            'sort-priority',
            $html
        );
    }

    public function test_icono_ascendente_muestra_flecha_hacia_arriba(): void
    {
        $this->request([
            'sort' => [
                'court' => 'asc',
            ],
        ]);

        $html = SortHelper::icon(
            'court',
            $this->allowedColumns
        )->toHtml();

        $this->assertStringContainsString(
            'ti-arrow-up',
            $html
        );

        $this->assertStringContainsString(
            'sort-priority',
            $html
        );

        $this->assertStringContainsString(
            '>1<',
            $html
        );
    }

    public function test_icono_descendente_muestra_flecha_hacia_abajo(): void
    {
        $this->request([
            'sort' => [
                'court' => 'desc',
            ],
        ]);

        $html = SortHelper::icon(
            'court',
            $this->allowedColumns
        )->toHtml();

        $this->assertStringContainsString(
            'ti-arrow-down',
            $html
        );

        $this->assertStringContainsString(
            'sort-priority',
            $html
        );
    }

    public function test_icono_muestra_la_prioridad_de_la_columna(): void
    {
        $this->request([
            'sort' => [
                'court' => 'asc',
                'start_time' => 'desc',
                'price' => 'asc',
            ],
        ]);

        $court = SortHelper::icon(
            'court',
            $this->allowedColumns
        )->toHtml();

        $startTime = SortHelper::icon(
            'start_time',
            $this->allowedColumns
        )->toHtml();

        $price = SortHelper::icon(
            'price',
            $this->allowedColumns
        )->toHtml();

        $this->assertStringContainsString('>1<', $court);
        $this->assertStringContainsString('>2<', $startTime);
        $this->assertStringContainsString('>3<', $price);
    }

    public function test_icono_de_columna_no_ordenada_no_muestra_prioridad(): void
    {
        $this->request([
            'sort' => [
                'court' => 'asc',
                'price' => 'desc',
            ],
        ]);

        $html = SortHelper::icon(
            'start_time',
            $this->allowedColumns
        )->toHtml();

        $this->assertStringContainsString(
            'ti-arrows-sort',
            $html
        );

        $this->assertStringNotContainsString(
            'sort-priority',
            $html
        );
    }

    public function test_icono_devuelve_html_seguro(): void
    {
        $this->request();

        $icon = SortHelper::icon(
            'court',
            $this->allowedColumns
        );

        $this->assertInstanceOf(
            \Illuminate\Support\HtmlString::class,
            $icon
        );
    }
}