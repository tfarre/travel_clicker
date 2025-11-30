<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Config;

use App\Domain\Config\DTO\GameConfigDTO;
use App\Domain\Config\GameConfigLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GameConfigLoader::class)]
final class GameConfigLoaderTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/fixtures';

        // Create fixtures directory if it doesn't exist
        if (!is_dir($this->fixturesDir)) {
            mkdir($this->fixturesDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up fixture files
        $files = glob($this->fixturesDir . '/*.yaml');
        if ($files !== false) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
        if (is_dir($this->fixturesDir)) {
            rmdir($this->fixturesDir);
        }
    }

    #[Test]
    public function it_loads_game_config_from_yaml_files(): void
    {
        $this->createFormulasFixture();
        $this->createBuildingsFixture();
        $this->createVerticalsFixture();

        $loader = new GameConfigLoader($this->fixturesDir);
        $config = $loader->load();

        self::assertInstanceOf(GameConfigDTO::class, $config);

        // Verify formulas
        self::assertSame(1.15, $config->formulas->costGrowthRate);
        self::assertSame(1, $config->formulas->visitorsPerClick);
        self::assertSame(100, $config->formulas->saleTriggerThreshold);
        self::assertSame(0.10, $config->formulas->conversionRate);
        self::assertSame(1.25, $config->formulas->verticalUpgradeGrowthRate);

        // Verify marketing buildings
        self::assertCount(2, $config->marketing);
        self::assertSame('flyers', $config->marketing[0]->id);
        self::assertSame(0.1, $config->marketing[0]->production);

        // Verify verticals
        self::assertCount(2, $config->verticals);
        self::assertSame('weekend', $config->verticals[0]->id);
        self::assertSame(100, $config->verticals[0]->attractivity);
        self::assertSame('safari', $config->verticals[1]->id);
        self::assertSame(5, $config->verticals[1]->attractivity);
    }

    #[Test]
    public function it_caches_loaded_config(): void
    {
        $this->createFormulasFixture();
        $this->createBuildingsFixture();
        $this->createVerticalsFixture();

        $loader = new GameConfigLoader($this->fixturesDir);

        $config1 = $loader->load();
        $config2 = $loader->load();

        self::assertSame($config1, $config2);
    }

    #[Test]
    public function it_throws_exception_for_missing_formulas_file(): void
    {
        $this->createBuildingsFixture();
        $this->createVerticalsFixture();

        $loader = new GameConfigLoader($this->fixturesDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configuration file not found');

        $loader->load();
    }

    #[Test]
    public function it_throws_exception_for_missing_required_key(): void
    {
        // Create formulas file with missing key
        file_put_contents($this->fixturesDir . '/formulas.yaml', <<<YAML
formulas:
    cost_growth_rate: 1.15
    # Missing other required keys
YAML);
        $this->createBuildingsFixture();
        $this->createVerticalsFixture();

        $loader = new GameConfigLoader($this->fixturesDir);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Missing required key 'visitors_per_click'");

        $loader->load();
    }

    private function createFormulasFixture(): void
    {
        $yaml = <<<YAML
formulas:
    cost_growth_rate: 1.15
    visitors_per_click: 1
    sale_trigger_threshold: 100
    conversion_rate: 0.10
    base_commission_rate: 0.10
    vertical_upgrade_growth_rate: 1.25
    tick_interval_ms: 100
YAML;
        file_put_contents($this->fixturesDir . '/formulas.yaml', $yaml);
    }

    private function createBuildingsFixture(): void
    {
        $yaml = <<<YAML
marketing:
    -   id: flyers
        name: "Flyers"
        description: "Test flyers"
        icon: "📢"
        base_cost: 1000
        production: 0.1
    -   id: seo
        name: "SEO"
        description: "Test SEO"
        icon: "🔍"
        base_cost: 5000
        production: 0.5
YAML;
        file_put_contents($this->fixturesDir . '/buildings.yaml', $yaml);
    }

    private function createVerticalsFixture(): void
    {
        $yaml = <<<YAML
verticals:
    -   id: weekend
        name: "Week-end"
        description: "Courts séjours"
        icon: "🏡"
        base_price: 3000
        attractivity: 100
        margin_growth_factor: 1.05
        unlock_cost: 0
    -   id: safari
        name: "Safari"
        description: "Safari photo"
        icon: "🦁"
        base_price: 250000
        attractivity: 5
        margin_growth_factor: 1.12
        unlock_cost: 500000
YAML;
        file_put_contents($this->fixturesDir . '/verticals.yaml', $yaml);
    }
}
