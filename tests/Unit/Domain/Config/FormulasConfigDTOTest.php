<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Config;

use App\Domain\Config\DTO\FormulasConfigDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FormulasConfigDTO::class)]
final class FormulasConfigDTOTest extends TestCase
{
    private FormulasConfigDTO $formulas;

    protected function setUp(): void
    {
        $this->formulas = new FormulasConfigDTO(
            costGrowthRate: 1.15,
            visitorsPerClick: 1,
            saleTriggerThreshold: 100,
            conversionRate: 0.10,
            baseCommissionRate: 0.10,
            verticalUpgradeGrowthRate: 1.25,
            tickIntervalMs: 100,
        );
    }

    #[Test]
    public function it_stores_all_formula_values(): void
    {
        self::assertSame(1.15, $this->formulas->costGrowthRate);
        self::assertSame(1, $this->formulas->visitorsPerClick);
        self::assertSame(100, $this->formulas->saleTriggerThreshold);
        self::assertSame(0.10, $this->formulas->conversionRate);
        self::assertSame(0.10, $this->formulas->baseCommissionRate);
        self::assertSame(1.25, $this->formulas->verticalUpgradeGrowthRate);
        self::assertSame(100, $this->formulas->tickIntervalMs);
    }

    #[Test]
    public function it_calculates_building_cost_for_first_purchase(): void
    {
        // First purchase: baseCost × (1.15 ^ 0) = baseCost × 1 = 1000
        $cost = $this->formulas->calculateBuildingCost(baseCost: 1000, owned: 0);

        self::assertSame(1000, $cost);
    }

    #[Test]
    public function it_calculates_building_cost_with_growth(): void
    {
        // Second purchase: 1000 × (1.15 ^ 1) = 1150
        $cost = $this->formulas->calculateBuildingCost(baseCost: 1000, owned: 1);
        self::assertSame(1150, $cost);

        // Third purchase: 1000 × (1.15 ^ 2) = 1322.5 → 1322 (floor)
        $cost = $this->formulas->calculateBuildingCost(baseCost: 1000, owned: 2);
        self::assertSame(1322, $cost);

        // 10th purchase: 1000 × (1.15 ^ 9) ≈ 3517
        $cost = $this->formulas->calculateBuildingCost(baseCost: 1000, owned: 9);
        self::assertSame(3517, $cost);
    }

    #[Test]
    public function it_calculates_commission(): void
    {
        // 10% commission on 5000 centimes (50€) = 500 centimes (5€)
        $commission = $this->formulas->calculateCommission(saleValue: 5000);
        self::assertSame(500, $commission);

        // 10% commission on 8000 centimes (80€) = 800 centimes (8€)
        $commission = $this->formulas->calculateCommission(saleValue: 8000);
        self::assertSame(800, $commission);
    }

    #[Test]
    public function it_floors_commission_for_odd_values(): void
    {
        // 10% commission on 5555 centimes = 555.5 → 555 (floor)
        $commission = $this->formulas->calculateCommission(saleValue: 5555);
        self::assertSame(555, $commission);
    }

    #[Test]
    public function it_calculates_buyers_from_visitors(): void
    {
        // 10% conversion: 100 visitors = 10 buyers
        $buyers = $this->formulas->calculateBuyers(visitors: 100);
        self::assertSame(10.0, $buyers);

        // 10% conversion: 50 visitors = 5 buyers
        $buyers = $this->formulas->calculateBuyers(visitors: 50);
        self::assertSame(5.0, $buyers);
    }

    #[Test]
    public function it_calculates_vertical_upgrade_cost(): void
    {
        // Level 0 (unlock): baseCost × (1.25 ^ 0) = baseCost
        $cost = $this->formulas->calculateVerticalUpgradeCost(baseCost: 10000, level: 0);
        self::assertSame(10000, $cost);

        // Level 1 (upgrade to 2): 10000 × (1.25 ^ 1) = 12500
        $cost = $this->formulas->calculateVerticalUpgradeCost(baseCost: 10000, level: 1);
        self::assertSame(12500, $cost);

        // Level 2 (upgrade to 3): 10000 × (1.25 ^ 2) = 15625
        $cost = $this->formulas->calculateVerticalUpgradeCost(baseCost: 10000, level: 2);
        self::assertSame(15625, $cost);
    }
}
