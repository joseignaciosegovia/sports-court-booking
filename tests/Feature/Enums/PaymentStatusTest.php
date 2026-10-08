<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentStatusTest extends TestCase
{
    #[DataProvider('statusProvider')]
    public function test_it_returns_the_correct_label_and_badge_data(
        PaymentStatus $status,
        string $label,
        string $badgeColor,
        string $badgeIcon
    ): void {
        $this->assertSame($label, $status->label());
        $this->assertSame($badgeColor, $status->badgeColor());
        $this->assertSame($badgeIcon, $status->badgeIcon());
    }

    public static function statusProvider(): array
    {
        return [
            'paid' => [
                PaymentStatus::Paid,
                'Pagada',
                'green',
                'ti ti-circle-check',
            ],
            'pending' => [
                PaymentStatus::Pending,
                'Pendiente de pago',
                'blue',
                'ti ti-clock',
            ],
            'canceled' => [
                PaymentStatus::Canceled,
                'Cancelada',
                'red',
                'ti ti-ban',
            ],
            'refunded' => [
                PaymentStatus::Refunded,
                'Reembolsada',
                'purple',
                'ti ti-receipt-refund',
            ],
        ];
    }

    #[DataProvider('canceledProvider')]
    public function test_it_knows_if_a_status_is_canceled(
        PaymentStatus $status,
        bool $expected
    ): void {
        $this->assertSame($expected, $status->isCanceled());
    }

    public static function canceledProvider(): array
    {
        return [
            'paid' => [PaymentStatus::Paid, false],
            'pending' => [PaymentStatus::Pending, false],
            'canceled' => [PaymentStatus::Canceled, true],
            'refunded' => [PaymentStatus::Refunded, true],
        ];
    }

    #[DataProvider('payableProvider')]
    public function test_it_knows_if_a_status_is_payable(
        PaymentStatus $status,
        bool $expected
    ): void {
        $this->assertSame($expected, $status->isPayable());
    }

    public static function payableProvider(): array
    {
        return [
            'paid' => [PaymentStatus::Paid, false],
            'pending' => [PaymentStatus::Pending, true],
            'canceled' => [PaymentStatus::Canceled, false],
            'refunded' => [PaymentStatus::Refunded, false],
        ];
    }

    #[DataProvider('cancelableProvider')]
    public function test_it_knows_if_a_status_can_be_canceled(
        PaymentStatus $status,
        bool $expected
    ): void {
        $this->assertSame($expected, $status->canBeCanceled());
    }

    public static function cancelableProvider(): array
    {
        return [
            'paid' => [PaymentStatus::Paid, true],
            'pending' => [PaymentStatus::Pending, true],
            'canceled' => [PaymentStatus::Canceled, false],
            'refunded' => [PaymentStatus::Refunded, false],
        ];
    }
}