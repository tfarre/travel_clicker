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
            type: 'marketing',
            production: 0.1,
            cartBonus: null,
        );

        self::assertSame('flyers', $building->id);
        self::assertSame('Flyers', $building->name);
        self::assertSame('Distribution de flyers', $building->description);
        self::assertSame('📢', $building->icon);
        self::assertSame(1000, $building->baseCost);
        self::assertSame('marketing', $building->type);
        self::assertSame(0.1, $building->production);
        self::assertNull($building->cartBonus);
    }

    #[Test]
    public function it_creates_a_partner_building(): void
    {
        $building = new BuildingConfigDTO(
            id: 'gite_rural',
            name: 'Gîte Rural',
            description: 'Partenariat avec des gîtes',
            icon: '🏠',
            baseCost: 10000,
            type: 'partner',
            production: null,
            cartBonus: 1000,
        );

        self::assertSame('gite_rural', $building->id);
        self::assertSame('Gîte Rural', $building->name);
        self::assertSame(10000, $building->baseCost);
        self::assertSame('partner', $building->type);
        self::assertNull($building->production);
        self::assertSame(1000, $building->cartBonus);
    }

    #[Test]
    public function it_identifies_marketing_buildings(): void
    {
        $marketing = new BuildingConfigDTO(
            id: 'test',
            name: 'Test',
            description: '',
            icon: '🔍',
            baseCost: 1000,
            type: 'marketing',
            production: 1.0,
        );

        self::assertTrue($marketing->isMarketing());
        self::assertFalse($marketing->isPartner());
    }

    #[Test]
    public function it_identifies_partner_buildings(): void
    {
        $partner = new BuildingConfigDTO(
            id: 'test',
            name: 'Test',
            description: '',
            icon: '🏨',
            baseCost: 1000,
            type: 'partner',
            cartBonus: 500,
        );

        self::assertFalse($partner->isMarketing());
        self::assertTrue($partner->isPartner());
    }
}
