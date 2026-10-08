<?php

namespace Tests\Feature\QueryFilters;

use App\QueryFilters\ReservationFilter;
use Illuminate\Http\Request;
use Tests\TestCase;
use App\Models\Reservation;

class ReservationFilterTest extends TestCase
{
    public function test_it_filters_by_court_id(): void
    {
        $request = Request::create('/', 'GET', [
            'court_id' => 5,
        ]);

        $query = Reservation::query();

        $result = (new ReservationFilter($request))->apply($query);

        $this->assertSame(
            [5],
            $result->getBindings()
        );
    }

    public function test_it_filters_by_location(): void
    {
        $request = Request::create('/', 'GET', [
            'location' => 'Pista 1',
        ]);

        $query = Reservation::query();

        $result = (new ReservationFilter($request))->apply($query);

        $this->assertSame(
            ['Pista 1'],
            $result->getBindings()
        );
    }

    public function test_it_filters_by_status(): void
    {
        $request = Request::create('/', 'GET', [
            'status' => 'paid',
        ]);

        $query = Reservation::query();

        $result = (new ReservationFilter($request))->apply($query);

        $this->assertSame(
            ['paid'],
            $result->getBindings()
        );
    }

    public function test_it_filters_by_date(): void
    {
        $request = Request::create('/', 'GET', [
            'date' => '2026-10-08',
        ]);

        $query = Reservation::query();

        $result = (new ReservationFilter($request))->apply($query);

        $this->assertSame(
            ['2026-10-08'],
            $result->getBindings()
        );
    }

    public function test_it_filters_by_past_date_range(): void
    {
        $request = Request::create('/', 'GET', [
            'date_range' => 'past',
        ]);

        $query = Reservation::query();

        $result = (new ReservationFilter($request))->apply($query);

        $this->assertNotEmpty($result->getBindings());
    }

    public function test_it_filters_by_future_date_range(): void
    {
        $request = Request::create('/', 'GET', [
            'date_range' => 'future',
        ]);

        $query = Reservation::query();

        $result = (new ReservationFilter($request))->apply($query);

        $this->assertNotEmpty($result->getBindings());
    }

    public function test_it_does_not_apply_date_range_for_invalid_value(): void
    {
        $request = Request::create('/', 'GET', [
            'date_range' => 'invalid',
        ]);

        $query = Reservation::query();

        $result = (new ReservationFilter($request))->apply($query);

        $this->assertSame([], $result->getBindings());
    }

    public function test_it_filters_by_canceled_by(): void
    {
        $request = Request::create('/', 'GET', [
            'canceled_by' => 'client',
        ]);

        $query = Reservation::query();

        $result = (new ReservationFilter($request))->apply($query);

        $this->assertSame(
            ['client'],
            $result->getBindings()
        );
    }

    public function test_it_ignores_empty_filter_values(): void
    {
        $request = Request::create('/', 'GET', [
            'court_id' => '',
            'location' => null,
            'status' => '',
            'date' => null,
            'date_range' => '',
            'canceled_by' => null,
        ]);

        $query = Reservation::query();

        $result = (new ReservationFilter($request))->apply($query);

        $this->assertSame([], $result->getBindings());
    }

    public function test_it_ignores_unknown_filters(): void
    {
        $request = Request::create('/', 'GET', [
            'unknown' => 'value',
            'foo' => 'bar',
        ]);

        $query = Reservation::query();

        $result = (new ReservationFilter($request))->apply($query);

        $this->assertSame([], $result->getBindings());
    }
}