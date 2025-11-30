<?php

declare(strict_types=1);

namespace App\Domain\Config\DTO;

/**
 * Root configuration object containing all game settings.
 *
 * This is the main DTO that will be serialized to JSON and passed to the frontend.
 * It acts as the single source of truth for all game rules and balancing.
 */
readonly class GameConfigDTO
{
    /**
     * @param FormulasConfigDTO        $formulas  Game formulas and coefficients
     * @param array<BuildingConfigDTO> $marketing Marketing buildings (passive visitors)
     * @param array<VerticalConfigDTO> $verticals Travel category verticals (revenue distribution)
     */
    public function __construct(
        public FormulasConfigDTO $formulas,
        public array $marketing,
        public array $verticals,
    ) {
    }

    /**
     * Find a marketing building by its ID.
     */
    public function findMarketing(string $id): ?BuildingConfigDTO
    {
        foreach ($this->marketing as $building) {
            if ($building->id === $id) {
                return $building;
            }
        }

        return null;
    }

    /**
     * Find a vertical by its ID.
     */
    public function findVertical(string $id): ?VerticalConfigDTO
    {
        foreach ($this->verticals as $vertical) {
            if ($vertical->id === $id) {
                return $vertical;
            }
        }

        return null;
    }

    /**
     * Get verticals that start unlocked (unlock_cost = 0).
     *
     * @return array<VerticalConfigDTO>
     */
    public function getStartingVerticals(): array
    {
        return array_filter(
            $this->verticals,
            fn (VerticalConfigDTO $v): bool => $v->startsUnlocked()
        );
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'formulas' => [
                'costGrowthRate' => $this->formulas->costGrowthRate,
                'visitorsPerClick' => $this->formulas->visitorsPerClick,
                'saleTriggerThreshold' => $this->formulas->saleTriggerThreshold,
                'conversionRate' => $this->formulas->conversionRate,
                'baseCommissionRate' => $this->formulas->baseCommissionRate,
                'verticalUpgradeGrowthRate' => $this->formulas->verticalUpgradeGrowthRate,
                'tickIntervalMs' => $this->formulas->tickIntervalMs,
            ],
            'marketing' => array_map(
                fn (BuildingConfigDTO $b): array => $this->buildingToArray($b),
                $this->marketing
            ),
            'verticals' => array_map(
                fn (VerticalConfigDTO $v): array => $this->verticalToArray($v),
                $this->verticals
            ),
        ];
    }

    /**
     * Convert a building DTO to array.
     *
     * @return array<string, mixed>
     */
    private function buildingToArray(BuildingConfigDTO $building): array
    {
        return [
            'id' => $building->id,
            'name' => $building->name,
            'description' => $building->description,
            'icon' => $building->icon,
            'baseCost' => $building->baseCost,
            'production' => $building->production,
        ];
    }

    /**
     * Convert a vertical DTO to array.
     *
     * @return array<string, mixed>
     */
    private function verticalToArray(VerticalConfigDTO $vertical): array
    {
        return [
            'id' => $vertical->id,
            'name' => $vertical->name,
            'description' => $vertical->description,
            'icon' => $vertical->icon,
            'basePrice' => $vertical->basePrice,
            'attractivity' => $vertical->attractivity,
            'marginGrowthFactor' => $vertical->marginGrowthFactor,
            'unlockCost' => $vertical->unlockCost,
        ];
    }
}
