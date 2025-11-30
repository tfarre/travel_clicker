<?php

declare(strict_types=1);

namespace App\Domain\Config;

use App\Domain\Config\DTO\BuildingConfigDTO;
use App\Domain\Config\DTO\FormulasConfigDTO;
use App\Domain\Config\DTO\GameConfigDTO;
use App\Domain\Config\DTO\VerticalConfigDTO;
use Symfony\Component\Yaml\Yaml;

/**
 * Service responsible for loading and parsing game configuration from YAML files.
 *
 * This loader reads the configuration files from config/game/ directory,
 * validates the structure, and hydrates the corresponding DTOs.
 */
final class GameConfigLoader
{
    private ?GameConfigDTO $cachedConfig = null;

    public function __construct(
        private readonly string $configDir,
    ) {
    }

    /**
     * Load the complete game configuration.
     *
     * Results are cached in memory for the duration of the request.
     */
    public function load(): GameConfigDTO
    {
        if ($this->cachedConfig !== null) {
            return $this->cachedConfig;
        }

        $formulas = $this->loadFormulas();
        $marketing = $this->loadMarketing();
        $verticals = $this->loadVerticals();

        $this->cachedConfig = new GameConfigDTO(
            formulas: $formulas,
            marketing: $marketing,
            verticals: $verticals,
        );

        return $this->cachedConfig;
    }

    /**
     * Load and parse the formulas configuration.
     */
    private function loadFormulas(): FormulasConfigDTO
    {
        $path = $this->configDir . '/formulas.yaml';
        $data = $this->parseYamlFile($path);

        if (!isset($data['formulas'])) {
            throw new \RuntimeException('Missing "formulas" key in formulas.yaml');
        }

        $f = $data['formulas'];

        return new FormulasConfigDTO(
            costGrowthRate: (float) ($f['cost_growth_rate'] ?? throw $this->missingKey('cost_growth_rate')),
            visitorsPerClick: (int) ($f['visitors_per_click'] ?? throw $this->missingKey('visitors_per_click')),
            saleTriggerThreshold: (int) ($f['sale_trigger_threshold'] ?? throw $this->missingKey('sale_trigger_threshold')),
            conversionRate: (float) ($f['conversion_rate'] ?? throw $this->missingKey('conversion_rate')),
            baseCommissionRate: (float) ($f['base_commission_rate'] ?? throw $this->missingKey('base_commission_rate')),
            verticalUpgradeGrowthRate: (float) ($f['vertical_upgrade_growth_rate'] ?? throw $this->missingKey('vertical_upgrade_growth_rate')),
            tickIntervalMs: (int) ($f['tick_interval_ms'] ?? throw $this->missingKey('tick_interval_ms')),
        );
    }

    /**
     * Load and parse the marketing buildings configuration.
     *
     * @return array<BuildingConfigDTO>
     */
    private function loadMarketing(): array
    {
        $path = $this->configDir . '/buildings.yaml';
        $data = $this->parseYamlFile($path);

        $marketing = [];

        if (isset($data['marketing']) && is_array($data['marketing'])) {
            foreach ($data['marketing'] as $buildingData) {
                $marketing[] = $this->parseBuilding($buildingData);
            }
        }

        return $marketing;
    }

    /**
     * Load and parse the verticals configuration.
     *
     * @return array<VerticalConfigDTO>
     */
    private function loadVerticals(): array
    {
        $path = $this->configDir . '/verticals.yaml';
        $data = $this->parseYamlFile($path);

        $verticals = [];

        if (isset($data['verticals']) && is_array($data['verticals'])) {
            foreach ($data['verticals'] as $verticalData) {
                $verticals[] = $this->parseVertical($verticalData);
            }
        }

        return $verticals;
    }

    /**
     * Parse a single building from YAML data.
     *
     * @param array<string, mixed> $data Building data from YAML
     */
    private function parseBuilding(array $data): BuildingConfigDTO
    {
        $id = $data['id'] ?? throw $this->missingKey('id', 'building');

        return new BuildingConfigDTO(
            id: (string) $id,
            name: (string) ($data['name'] ?? throw $this->missingKey('name', "building '{$id}'")),
            description: (string) ($data['description'] ?? ''),
            icon: (string) ($data['icon'] ?? '🏢'),
            baseCost: (int) ($data['base_cost'] ?? throw $this->missingKey('base_cost', "building '{$id}'")),
            production: (float) ($data['production'] ?? throw $this->missingKey('production', "building '{$id}'")),
        );
    }

    /**
     * Parse a single vertical from YAML data.
     *
     * @param array<string, mixed> $data Vertical data from YAML
     */
    private function parseVertical(array $data): VerticalConfigDTO
    {
        $id = $data['id'] ?? throw $this->missingKey('id', 'vertical');

        return new VerticalConfigDTO(
            id: (string) $id,
            name: (string) ($data['name'] ?? throw $this->missingKey('name', "vertical '{$id}'")),
            description: (string) ($data['description'] ?? ''),
            icon: (string) ($data['icon'] ?? '✈️'),
            basePrice: (int) ($data['base_price'] ?? throw $this->missingKey('base_price', "vertical '{$id}'")),
            attractivity: (int) ($data['attractivity'] ?? throw $this->missingKey('attractivity', "vertical '{$id}'")),
            marginGrowthFactor: (float) ($data['margin_growth_factor'] ?? throw $this->missingKey('margin_growth_factor', "vertical '{$id}'")),
            unlockCost: (int) ($data['unlock_cost'] ?? 0),
        );
    }

    /**
     * Parse a YAML file and return its contents.
     *
     * @return array<string, mixed>
     */
    private function parseYamlFile(string $path): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Configuration file not found: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Failed to read configuration file: {$path}");
        }

        $parsed = Yaml::parse($content);

        if (!is_array($parsed)) {
            throw new \RuntimeException("Invalid YAML structure in: {$path}");
        }

        return $parsed;
    }

    /**
     * Create a missing key exception.
     */
    private function missingKey(string $key, string $context = 'config'): \RuntimeException
    {
        return new \RuntimeException("Missing required key '{$key}' in {$context}");
    }
}
