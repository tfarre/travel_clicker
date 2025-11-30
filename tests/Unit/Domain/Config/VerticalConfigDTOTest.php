<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Config;

use App\Domain\Config\DTO\VerticalConfigDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(VerticalConfigDTO::class)]
final class VerticalConfigDTOTest extends TestCase
{
    #[Test]
    public function it_creates_a_vertical(): void
    {
        $vertical = new VerticalConfigDTO(
            id: 'safari',
            name: 'Safari',
            description: 'Safari photo en Afrique',
            icon: '🦁',
            basePrice: 250000,
            attractivity: 5,
            marginGrowthFactor: 1.12,
            unlockCost: 500000,
        );

        self::assertSame('safari', $vertical->id);
        self::assertSame('Safari', $vertical->name);
        self::assertSame('Safari photo en Afrique', $vertical->description);
        self::assertSame('🦁', $vertical->icon);
        self::assertSame(250000, $vertical->basePrice);
        self::assertSame(5, $vertical->attractivity);
        self::assertSame(1.12, $vertical->marginGrowthFactor);
        self::assertSame(500000, $vertical->unlockCost);
    }

    #[Test]
    public function it_identifies_starting_unlocked_verticals(): void
    {
        $free = new VerticalConfigDTO(
            id: 'weekend',
            name: 'Week-end',
            description: 'Courts séjours',
            icon: '🏡',
            basePrice: 3000,
            attractivity: 100,
            marginGrowthFactor: 1.05,
            unlockCost: 0,
        );

        $locked = new VerticalConfigDTO(
            id: 'safari',
            name: 'Safari',
            description: 'Safari photo',
            icon: '🦁',
            basePrice: 250000,
            attractivity: 5,
            marginGrowthFactor: 1.12,
            unlockCost: 500000,
        );

        self::assertTrue($free->startsUnlocked());
        self::assertFalse($locked->startsUnlocked());
    }

    #[Test]
    public function it_calculates_price_at_level_1(): void
    {
        $vertical = new VerticalConfigDTO(
            id: 'randonnee',
            name: 'Randonnée',
            description: 'Treks',
            icon: '🥾',
            basePrice: 5000,
            attractivity: 70,
            marginGrowthFactor: 1.07,
            unlockCost: 10000,
        );

        // Level 1: basePrice × (1.07 ^ 0) = basePrice
        $price = $vertical->calculatePriceAtLevel(1);
        self::assertSame(5000, $price);
    }

    #[Test]
    public function it_calculates_price_with_level_growth(): void
    {
        $vertical = new VerticalConfigDTO(
            id: 'randonnee',
            name: 'Randonnée',
            description: 'Treks',
            icon: '🥾',
            basePrice: 5000,
            attractivity: 70,
            marginGrowthFactor: 1.07,
            unlockCost: 10000,
        );

        // Level 2: 5000 × (1.07 ^ 1) = 5350
        $price = $vertical->calculatePriceAtLevel(2);
        self::assertSame(5350, $price);

        // Level 3: 5000 × (1.07 ^ 2) = 5724.5 → 5724
        $price = $vertical->calculatePriceAtLevel(3);
        self::assertSame(5724, $price);

        // Level 10: 5000 × (1.07 ^ 9) ≈ 9192
        $price = $vertical->calculatePriceAtLevel(10);
        self::assertSame(9192, $price);
    }

    #[Test]
    public function it_returns_zero_for_level_zero(): void
    {
        $vertical = new VerticalConfigDTO(
            id: 'safari',
            name: 'Safari',
            description: 'Safari photo',
            icon: '🦁',
            basePrice: 250000,
            attractivity: 5,
            marginGrowthFactor: 1.12,
            unlockCost: 500000,
        );

        // Level 0 = not unlocked = no revenue
        $price = $vertical->calculatePriceAtLevel(0);
        self::assertSame(0, $price);
    }
}
