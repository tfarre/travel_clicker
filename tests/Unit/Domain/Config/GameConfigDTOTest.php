<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Config;

use App\Domain\Config\DTO\BuildingConfigDTO;
use App\Domain\Config\DTO\FormulasConfigDTO;
use App\Domain\Config\DTO\GameConfigDTO;
use App\Domain\Config\DTO\VerticalConfigDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GameConfigDTO::class)]
final class GameConfigDTOTest extends TestCase
{
    private GameConfigDTO $config;

    protected function setUp(): void
    {
        $formulas = new FormulasConfigDTO(
            costGrowthRate: 1.15,
            visitorsPerClick: 1,
            saleTriggerThreshold: 100,
            conversionRate: 0.10,
            baseCommissionRate: 0.10,
            verticalUpgradeGrowthRate: 1.25,
            tickIntervalMs: 100,
        );

        $marketing = [
            new BuildingConfigDTO(
                id: 'flyers',
                name: 'Flyers',
                description: 'Flyers description',
                icon: '📢',
                baseCost: 1000,
                type: 'marketing',
                production: 0.1,
            ),
            new BuildingConfigDTO(
                id: 'seo',
                name: 'SEO',
                description: 'SEO description',
                icon: '🔍',
                baseCost: 5000,
                type: 'marketing',
                production: 0.5,
            ),
        ];

        $verticals = [
            new VerticalConfigDTO(
                id: 'weekend',
                name: 'Week-end',
                description: 'Courts séjours',
                icon: '🏡',
                basePrice: 3000,
                attractivity: 100,
                marginGrowthFactor: 1.05,
                unlockCost: 0,
            ),
            new VerticalConfigDTO(
                id: 'safari',
                name: 'Safari',
                description: 'Safari photo',
                icon: '🦁',
                basePrice: 250000,
                attractivity: 5,
                marginGrowthFactor: 1.12,
                unlockCost: 500000,
            ),
        ];

        $this->config = new GameConfigDTO(
            formulas: $formulas,
            marketing: $marketing,
            verticals: $verticals,
        );
    }

    #[Test]
    public function it_finds_marketing_by_id(): void
    {
        $building = $this->config->findMarketing('seo');

        self::assertNotNull($building);
        self::assertSame('SEO', $building->name);
        self::assertSame(0.5, $building->production);
    }

    #[Test]
    public function it_returns_null_for_unknown_marketing(): void
    {
        $building = $this->config->findMarketing('unknown');

        self::assertNull($building);
    }

    #[Test]
    public function it_finds_vertical_by_id(): void
    {
        $vertical = $this->config->findVertical('safari');

        self::assertNotNull($vertical);
        self::assertSame('Safari', $vertical->name);
        self::assertSame(5, $vertical->attractivity);
    }

    #[Test]
    public function it_returns_null_for_unknown_vertical(): void
    {
        $vertical = $this->config->findVertical('unknown');

        self::assertNull($vertical);
    }

    #[Test]
    public function it_gets_starting_verticals(): void
    {
        $starting = $this->config->getStartingVerticals();

        self::assertCount(1, $starting);
        self::assertSame('weekend', array_values($starting)[0]->id);
    }

    #[Test]
    public function it_converts_to_array_for_json(): void
    {
        $array = $this->config->toArray();

        // Check formulas
        self::assertArrayHasKey('formulas', $array);
        self::assertSame(1.15, $array['formulas']['costGrowthRate']);
        self::assertSame(100, $array['formulas']['saleTriggerThreshold']);
        self::assertSame(0.10, $array['formulas']['conversionRate']);
        self::assertSame(1.25, $array['formulas']['verticalUpgradeGrowthRate']);

        // Check marketing buildings
        self::assertArrayHasKey('marketing', $array);
        self::assertCount(2, $array['marketing']);
        self::assertSame('flyers', $array['marketing'][0]['id']);
        self::assertSame(0.1, $array['marketing'][0]['production']);

        // Check verticals
        self::assertArrayHasKey('verticals', $array);
        self::assertCount(2, $array['verticals']);
        self::assertSame('weekend', $array['verticals'][0]['id']);
        self::assertSame(3000, $array['verticals'][0]['basePrice']);
        self::assertSame(100, $array['verticals'][0]['attractivity']);
        self::assertSame(0, $array['verticals'][0]['unlockCost']);

        self::assertSame('safari', $array['verticals'][1]['id']);
        self::assertSame(250000, $array['verticals'][1]['basePrice']);
        self::assertSame(5, $array['verticals'][1]['attractivity']);
        self::assertSame(500000, $array['verticals'][1]['unlockCost']);
    }
}
