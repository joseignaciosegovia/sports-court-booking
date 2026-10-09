<?php

namespace Tests\Unit;

use App\Rules\ValidDni;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ValidDniTest extends TestCase
{
    private function pasa(string $dni): bool
    {
        return Validator::make(['dni' => $dni], ['dni' => [new ValidDni]])->passes();
    }

    public static function validos(): array
    {
        return ['letra Z' => ['12345678Z'], 'letra T' => ['00000000T']];
    }

    public static function invalidos(): array
    {
        return [
            'letra incorrecta' => ['12345678A'],
            'muy corto'        => ['1234'],
            'sin números'      => ['ABCDEFGHI'],
            'sin letra'        => ['12345678'],
        ];
    }

    #[DataProvider('validos')]
    public function test_acepta_dnis_validos(string $dni): void
    {
        $this->assertTrue($this->pasa($dni));
    }

    #[DataProvider('invalidos')]
    public function test_rechaza_dnis_invalidos(string $dni): void
    {
        $this->assertFalse($this->pasa($dni));
    }
}