<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    #[DataProvider('rolesProvider')]
    public function test_it_returns_the_correct_label(
        UserRole $role,
        string $expectedLabel
    ): void {
        $this->assertSame($expectedLabel, $role->label());
    }

    public static function rolesProvider(): array
    {
        return [
            'client' => [
                UserRole::Client,
                'Cliente',
            ],
            'manager' => [
                UserRole::Manager,
                'Gestor',
            ],
            'admin' => [
                UserRole::Admin,
                'Administrador',
            ],
        ];
    }

    public function test_manager_has_blue_badge(): void
    {
        $this->assertSame('blue', UserRole::Manager->badgeColor());
    }

    public function test_admin_has_purple_badge(): void
    {
        $this->assertSame('purple', UserRole::Admin->badgeColor());
    }
}