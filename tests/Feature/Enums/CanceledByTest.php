<?php

namespace Tests\Feature;

use App\Enums\CanceledBy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CanceledByTest extends TestCase
{
    #[DataProvider('labelsProvider')]
    public function test_it_returns_the_correct_label(
        CanceledBy $canceledBy,
        string $expectedLabel
    ): void {
        $this->assertSame($expectedLabel, $canceledBy->label());
    }

    public static function labelsProvider(): array
    {
        return [
            'manager' => [
                CanceledBy::Manager,
                'Cancelada por Gestión',
            ],
            'client' => [
                CanceledBy::Client,
                'Cancelada por el Cliente',
            ],
            'system' => [
                CanceledBy::System,
                'Expiración automática',
            ],
        ];
    }
}