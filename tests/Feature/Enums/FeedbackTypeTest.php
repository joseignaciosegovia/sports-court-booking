<?php

namespace Tests\Feature;

use App\Enums\FeedbackType;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FeedbackTypeTest extends TestCase
{
    #[DataProvider('feedbackTypesProvider')]
    public function test_it_returns_the_correct_values(
        FeedbackType $type,
        string $label,
        string $badgeColor,
        string $badgeIcon
    ): void {
        $this->assertSame($label, $type->label());
        $this->assertSame($badgeColor, $type->badgeColor());
        $this->assertSame($badgeIcon, $type->badgeIcon());
    }

    public static function feedbackTypesProvider(): array
    {
        return [
            'suggestion' => [
                FeedbackType::Suggestion,
                'Sugerencia',
                'blue',
                'ti ti-bulb',
            ],
            'incident' => [
                FeedbackType::Incident,
                'Incidencia',
                'red',
                'ti ti-alert-triangle',
            ],
        ];
    }
}