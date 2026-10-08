<?php

namespace Tests\Feature\QueryFilters;

use App\Models\User;
use App\QueryFilters\UserFilter;
use Illuminate\Http\Request;
use Tests\TestCase;

class UserFilterTest extends TestCase
{
    public function test_it_filters_by_email(): void
    {
        $request = Request::create('/', 'GET', [
            'email' => 'john@example.com',
        ]);

        $query = User::query();

        $result = (new UserFilter($request))->apply($query);

        $this->assertSame(
            ['john@example.com'],
            $result->getBindings()
        );
    }

    public function test_it_filters_by_name(): void
    {
        $request = Request::create('/', 'GET', [
            'name' => 'John Doe',
        ]);

        $query = User::query();

        $result = (new UserFilter($request))->apply($query);

        $this->assertSame(
            ['John Doe'],
            $result->getBindings()
        );
    }

    public function test_it_filters_by_dni(): void
    {
        $request = Request::create('/', 'GET', [
            'dni' => '12345678A',
        ]);

        $query = User::query();

        $result = (new UserFilter($request))->apply($query);

        $this->assertSame(
            ['12345678A'],
            $result->getBindings()
        );
    }

    public function test_it_filters_by_phone(): void
    {
        $request = Request::create('/', 'GET', [
            'phone' => '600123456',
        ]);

        $query = User::query();

        $result = (new UserFilter($request))->apply($query);

        $this->assertSame(
            ['600123456'],
            $result->getBindings()
        );
    }

    public function test_it_filters_by_role(): void
    {
        $request = Request::create('/', 'GET', [
            'role' => 'manager',
        ]);

        $query = User::query();

        $result = (new UserFilter($request))->apply($query);

        $this->assertSame(
            ['manager'],
            $result->getBindings()
        );
    }

    public function test_it_ignores_empty_filter_values(): void
    {
        $request = Request::create('/', 'GET', [
            'email' => '',
            'name' => null,
        ]);

        $query = User::query();

        $result = (new UserFilter($request))->apply($query);

        $this->assertSame([], $result->getBindings());
    }

    public function test_it_ignores_unknown_filters(): void
    {
        $request = Request::create('/', 'GET', [
            'password' => 'secret',
            'unknown' => 'value',
        ]);

        $query = User::query();

        $result = (new UserFilter($request))->apply($query);

        $this->assertSame([], $result->getBindings());
    }
}