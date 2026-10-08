<?php

namespace Tests\Feature\QueryFilters;

use App\QueryFilters\QueryFilter;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Tests\TestCase;

class QueryFilterTest extends TestCase
{
    public function test_it_applies_filters_defined_in_the_request(): void
    {
        $request = Request::create('/', 'GET', [
            'email' => 'john@example.com',
        ]);

        $filter = new class($request) extends QueryFilter {
            protected function filterableFields(): array
            {
                return ['email'];
            }

            protected function email($value): void
            {
                $this->builder->where('users.email', $value);
            }
        };

        $query = User::query();

        $result = $filter->apply($query);

        $this->assertSame(
            ['john@example.com'],
            $result->getBindings()
        );
    }

    public function test_it_ignores_empty_values(): void
    {
        $request = Request::create('/', 'GET', [
            'email' => '',
        ]);

        $filter = new class($request) extends QueryFilter {
            protected function filterableFields(): array
            {
                return ['email'];
            }

            protected function email($value): void
            {
                $this->builder->where('users.email', $value);
            }
        };

        $query = User::query();

        $result = $filter->apply($query);

        $this->assertSame([], $result->getBindings());
    }

    public function test_it_ignores_null_values(): void
    {
        $request = Request::create('/', 'GET', [
            'email' => null,
        ]);

        $filter = new class($request) extends QueryFilter {
            protected function filterableFields(): array
            {
                return ['email'];
            }

            protected function email($value): void
            {
                $this->builder->where('users.email', $value);
            }
        };

        $query = User::query();

        $result = $filter->apply($query);

        $this->assertSame([], $result->getBindings());
    }

    public function test_it_ignores_fields_that_are_not_filterable(): void
    {
        $request = Request::create('/', 'GET', [
            'email' => 'john@example.com',
            'password' => 'secret',
        ]);

        $filter = new class($request) extends QueryFilter {
            protected function filterableFields(): array
            {
                return ['email'];
            }

            protected function email($value): void
            {
                $this->builder->where('users.email', $value);
            }
        };

        $query = User::query();

        $result = $filter->apply($query);

        $this->assertSame(
            ['john@example.com'],
            $result->getBindings()
        );

        $this->assertStringNotContainsString(
            'password',
            $result->toSql()
        );
    }

    public function test_it_ignores_filterable_fields_without_a_corresponding_method(): void
    {
        $request = Request::create('/', 'GET', [
            'email' => 'john@example.com',
            'name' => 'John',
        ]);

        $filter = new class($request) extends QueryFilter {
            protected function filterableFields(): array
            {
                return ['email', 'name'];
            }

            protected function email($value): void
            {
                $this->builder->where('users.email', $value);
            }
        };

        $query = User::query();

        $result = $filter->apply($query);

        $this->assertSame(
            ['john@example.com'],
            $result->getBindings()
        );
    }
}