<?php

declare(strict_types=1);

namespace App\Domain\Config\DTO;

/**
 * Immutable Data Transfer Object for game formula configuration.
 *
 * All monetary values are stored in CENTIMES (1€ = 100 centimes).
 * This ensures integer arithmetic and avoids floating-point precision issues.
 */
readonly class FormulasConfigDTO
{
    /**
     * @param float $costGrowthRate           Building cost multiplier per purchase (e.g., 1.15 = +15%)
     * @param int   $visitorsPerClick         Visitors gained per manual click
     * @param int   $saleTriggerThreshold     Number of visitors needed to trigger a sale batch
     * @param float $conversionRate           Percentage of visitors who become buyers (e.g., 0.10 = 10%)
     * @param float $baseCommissionRate       Commission percentage on each sale (e.g., 0.10 = 10%)
     * @param float $verticalUpgradeGrowthRate Vertical upgrade cost multiplier per level (e.g., 1.25 = +25%)
     * @param int   $tickIntervalMs           Game tick interval in milliseconds
     */
    public function __construct(
        public float $costGrowthRate,
        public int $visitorsPerClick,
        public int $saleTriggerThreshold,
        public float $conversionRate,
        public float $baseCommissionRate,
        public float $verticalUpgradeGrowthRate,
        public int $tickIntervalMs,
    ) {
    }

    /**
     * Calculate the cost of a building based on base cost and quantity owned.
     *
     * Formula: cost = baseCost × (costGrowthRate ^ owned)
     *
     * @param int $baseCost Base cost in centimes
     * @param int $owned    Number of buildings already owned
     *
     * @return int Calculated cost in centimes
     */
    public function calculateBuildingCost(int $baseCost, int $owned): int
    {
        return (int) floor($baseCost * ($this->costGrowthRate ** $owned));
    }

    /**
     * Calculate commission earned from a sale.
     *
     * @param int $saleValue Total sale value in centimes
     *
     * @return int Commission earned in centimes
     */
    public function calculateCommission(int $saleValue): int
    {
        return (int) floor($saleValue * $this->baseCommissionRate);
    }

    /**
     * Calculate number of buyers from visitors.
     *
     * @param int $visitors Number of visitors
     *
     * @return float Number of buyers (can be fractional for accumulation)
     */
    public function calculateBuyers(int $visitors): float
    {
        return $visitors * $this->conversionRate;
    }

    /**
     * Calculate the upgrade cost for a vertical at a given level.
     *
     * Formula: baseCost × (verticalUpgradeGrowthRate ^ level)
     *
     * @param int $baseCost  Base unlock cost in centimes
     * @param int $level     Current level (upgrade TO level+1)
     *
     * @return int Upgrade cost in centimes
     */
    public function calculateVerticalUpgradeCost(int $baseCost, int $level): int
    {
        return (int) floor($baseCost * ($this->verticalUpgradeGrowthRate ** $level));
    }
}
