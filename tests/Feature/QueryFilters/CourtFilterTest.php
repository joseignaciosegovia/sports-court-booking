<?php

namespace Tests\Feature\QueryFilters;

use App\Models\Court;
use App\QueryFilters\CourtFilter;
use Illuminate\Http\Request;
use Tests\TestCase;

class CourtFilterTest extends TestCase
{
    public function test_it_filters_by_name(): void
    {
        $request = Request::create('/', 'GET', [
            'name' => 'Pista Central',
        ]);

        $query = Court::query();

        $result = (new CourtFilter($request))->apply($query);

        $this->assertSame(
            ['Pista Central'],
            $result->getBindings()
        );
    }

    public function test_it_filters_by_location(): void
    {
        $request = Request::create('/', 'GET', [
            'location' => 'Madrid',
        ]);

        $query = Court::query();

        $result = (new CourtFilter($request))->apply($query);

        $this->assertSame(
            ['Madrid'],
            $result->getBindings()
        );
    }

    public function test_it_ignores_empty_filter_values(): void
    {
        $request = Request::create('/', 'GET', [
            'name' => '',
            'location' => null,
        ]);

        $query = Court::query();

        $result = (new CourtFilter($request))->apply($query);

        $this->assertSame([], $result->getBindings());
    }

    public function test_it_ignores_unknown_filters(): void
    {
        $request = Request::create('/', 'GET', [
            'unknown' => 'value',
        ]);

        $query = Court::query();

        $result = (new CourtFilter($request))->apply($query);

        $this->assertSame([], $result->getBindings());
    }
}