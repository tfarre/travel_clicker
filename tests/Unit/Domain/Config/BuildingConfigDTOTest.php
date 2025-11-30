<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Config;

use App\Domain\Config\DTO\BuildingConfigDTO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(BuildingConfigDTO::class)]
final class BuildingConfigDTOTest extends TestCase
{
    #[Test]
    public function it_creates_a_marketing_building(): void
    {
        $building = new BuildingConfigDTO(
            id: 'flyers',
            name: 'Flyers',
            description: 'Distribution de flyers',
            icon: '📢',
            baseCost: 1000,
            production: 0.1,
        );

        self::assertSame('flyers', $building->id);
        self::assertSame('Flyers', $building->name);
        self::assertSame('Distribution de flyers', $building->description);
        self::assertSame('📢', $building->icon);
        self::assertSame(1000, $building->baseCost);
        self::assertSame(0.1, $building->production);
    }

    #[Test]
    public function it_stores_production_value(): void
    {
        $building = new BuildingConfigDTO(
            id: 'seo_basic',
            name: 'SEO Basic',
            description: 'Référencement naturel',
            icon: '🔍',
            baseCost: 5000,
            production: 0.5,
        );

        self::assertSame('seo_basic', $building->id);
        self::assertSame(5000, $building->baseCost);
        self::assertSame(0.5, $building->production);
    }

    #[Test]
    public function it_handles_high_production_values(): void
    {
        $building = new BuildingConfigDTO(
            id: 'influencer',
            name: 'Influenceur',
            description: 'Marketing d\'influence',
            icon: '🌟',
            baseCost: 100000,
            production: 10.0,
        );

        self::assertSame('influencer', $building->id);
        self::assertSame(100000, $building->baseCost);
        self::assertSame(10.0, $building->production);
    }
}
