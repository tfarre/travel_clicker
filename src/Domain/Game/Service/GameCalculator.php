<?php

declare(strict_types=1);

namespace App\Domain\Game\Service;

use App\Domain\Config\DTO\GameConfigDTO;
use App\Domain\Config\DTO\VerticalConfigDTO;
use App\Domain\Game\DTO\BuildingsState;
use App\Domain\Game\DTO\GameState;
use App\Domain\Game\DTO\VerticalsState;

/**
 * Game Calculator Service - Server-Authoritative Game Logic
 *
 * This service contains ALL game calculations and is the single source of truth.
 * The frontend should NEVER perform these calculations directly.
 *
 * All monetary values are in CENTIMES (1€ = 100 centimes).
 */
final readonly class GameCalculator
{
    public function __construct(
        private GameConfigDTO $config,
    ) {
    }

    // =========================================================================
    // COST CALCULATIONS
    // =========================================================================

    /**
     * Calculate the cost of purchasing a building.
     *
     * Formula: baseCost × (costGrowthRate ^ owned)
     */
    public function calculateBuildingCost(string $buildingId, int $owned): int
    {
        $building = $this->config->findMarketing($buildingId);
        if ($building === null) {
            throw new \InvalidArgumentException("Unknown building: {$buildingId}");
        }

        return (int) floor(
            $building->baseCost * ($this->config->formulas->costGrowthRate ** $owned)
        );
    }

    /**
     * Calculate the cost of upgrading a vertical (unlock or level up).
     *
     * - Level 0 → 1: unlockCost
     * - Level N → N+1: unlockCost × (verticalUpgradeGrowthRate ^ level)
     */
    public function calculateVerticalUpgradeCost(string $verticalId, int $currentLevel): int
    {
        $vertical = $this->config->findVertical($verticalId);
        if ($vertical === null) {
            throw new \InvalidArgumentException("Unknown vertical: {$verticalId}");
        }

        if ($currentLevel === 0) {
            return $vertical->unlockCost;
        }

        return (int) floor(
            $vertical->unlockCost * ($this->config->formulas->verticalUpgradeGrowthRate ** $currentLevel)
        );
    }

    /**
     * Calculate the current price of a vertical at a given level.
     *
     * Formula: basePrice × (marginGrowthFactor ^ (level - 1))
     */
    public function calculateVerticalPrice(string $verticalId, int $level): int
    {
        $vertical = $this->config->findVertical($verticalId);
        if ($vertical === null) {
            throw new \InvalidArgumentException("Unknown vertical: {$verticalId}");
        }

        if ($level < 1) {
            return 0;
        }

        return (int) floor(
            $vertical->basePrice * ($vertical->marginGrowthFactor ** ($level - 1))
        );
    }

    // =========================================================================
    // PRODUCTION CALCULATIONS
    // =========================================================================

    /**
     * Calculate total visitors per second from all marketing buildings.
     */
    public function calculateVisitorsPerSecond(BuildingsState $buildings): float
    {
        $total = 0.0;

        foreach ($this->config->marketing as $building) {
            $owned = $buildings->getOwned($building->id);
            $total += $building->production * $owned;
        }

        return $total;
    }

    /**
     * Calculate total attractivity of all unlocked verticals.
     */
    public function calculateTotalAttractivity(VerticalsState $verticals): int
    {
        $total = 0;

        foreach ($this->config->verticals as $vertical) {
            if ($verticals->isUnlocked($vertical->id)) {
                $total += $vertical->attractivity;
            }
        }

        return $total;
    }

    // =========================================================================
    // REVENUE CALCULATIONS
    // =========================================================================

    /**
     * Calculate revenue distribution among unlocked verticals.
     *
     * @return array<array{id: string, marketShare: float, sales: float, revenue: int, currentPrice: int}>
     */
    public function calculateRevenueDistribution(float $buyers, VerticalsState $verticals): array
    {
        $totalAttractivity = $this->calculateTotalAttractivity($verticals);

        if ($totalAttractivity === 0) {
            return [];
        }

        $result = [];

        foreach ($this->config->verticals as $vertical) {
            $level = $verticals->getLevel($vertical->id);

            if ($level === 0) {
                continue; // Skip locked verticals
            }

            $marketShare = $vertical->attractivity / $totalAttractivity;
            $sales = $buyers * $marketShare;
            $currentPrice = $this->calculateVerticalPrice($vertical->id, $level);
            $revenue = (int) floor($sales * $currentPrice);

            $result[] = [
                'id' => $vertical->id,
                'marketShare' => $marketShare * 100,
                'sales' => $sales,
                'revenue' => $revenue,
                'currentPrice' => $currentPrice,
            ];
        }

        return $result;
    }

    /**
     * Process a batch of visitors through the sales funnel.
     *
     * @return array{salesCount: float, totalRevenue: int, commission: int}
     */
    public function processSaleBatch(int $visitors, VerticalsState $verticals): array
    {
        $totalAttractivity = $this->calculateTotalAttractivity($verticals);

        if ($totalAttractivity === 0) {
            return [
                'salesCount' => 0,
                'totalRevenue' => 0,
                'commission' => 0,
            ];
        }

        // Convert visitors to buyers
        $buyers = $visitors * $this->config->formulas->conversionRate;

        // Calculate revenue distribution
        $distribution = $this->calculateRevenueDistribution($buyers, $verticals);

        // Sum up totals
        $totalSaleValue = 0;
        $totalSalesCount = 0.0;

        foreach ($distribution as $item) {
            $totalSaleValue += $item['revenue'];
            $totalSalesCount += $item['sales'];
        }

        // Apply commission
        $commission = (int) floor($totalSaleValue * $this->config->formulas->baseCommissionRate);

        return [
            'salesCount' => $totalSalesCount,
            'totalRevenue' => $totalSaleValue,
            'commission' => $commission,
        ];
    }

    // =========================================================================
    // STATE MUTATIONS (Returns new immutable state)
    // =========================================================================

    /**
     * Process a click action and return the new state.
     */
    public function processClick(GameState $state, int $clicks = 1): GameState
    {
        $visitors = $this->config->formulas->visitorsPerClick * $clicks;

        return $this->addVisitors($state, $visitors);
    }

    /**
     * Process a tick (passive income) and return the new state.
     */
    public function processTick(GameState $state, int $elapsedMs): GameState
    {
        $visitorsPerSecond = $this->calculateVisitorsPerSecond($state->buildings);

        if ($visitorsPerSecond <= 0) {
            return $state;
        }

        $visitors = $visitorsPerSecond * ($elapsedMs / 1000);

        return $this->addVisitors($state, $visitors);
    }

    /**
     * Add visitors to the state and process any triggered sales.
     */
    public function addVisitors(GameState $state, float $count): GameState
    {
        $newTotalVisitors = $state->totalVisitors + (int) $count;
        $newVisitorsTowardsSale = $state->visitorsTowardsSale + $count;

        $threshold = $this->config->formulas->saleTriggerThreshold;
        $newMoney = $state->money;
        $newTotalSales = $state->totalSales;
        $newTotalRevenue = $state->totalRevenue;

        // Process sale batches
        while ($newVisitorsTowardsSale >= $threshold) {
            $newVisitorsTowardsSale -= $threshold;

            $saleResult = $this->processSaleBatch($threshold, $state->verticals);

            $newTotalSales += $saleResult['salesCount'];
            $newTotalRevenue += $saleResult['totalRevenue'];
            $newMoney += $saleResult['commission'];
        }

        return new GameState(
            money: $newMoney,
            totalVisitors: $newTotalVisitors,
            visitorsTowardsSale: $newVisitorsTowardsSale,
            totalSales: $newTotalSales,
            totalRevenue: $newTotalRevenue,
            buildings: $state->buildings,
            verticals: $state->verticals,
            timestamp: time(),
        );
    }

    /**
     * Attempt to buy a building. Returns new state or null if cannot afford.
     */
    public function buyBuilding(GameState $state, string $buildingId): ?GameState
    {
        $building = $this->config->findMarketing($buildingId);
        if ($building === null) {
            return null;
        }

        $owned = $state->buildings->getOwned($buildingId);
        $cost = $this->calculateBuildingCost($buildingId, $owned);

        if ($state->money < $cost) {
            return null; // Cannot afford
        }

        return new GameState(
            money: $state->money - $cost,
            totalVisitors: $state->totalVisitors,
            visitorsTowardsSale: $state->visitorsTowardsSale,
            totalSales: $state->totalSales,
            totalRevenue: $state->totalRevenue,
            buildings: $state->buildings->withPurchase($buildingId),
            verticals: $state->verticals,
            timestamp: time(),
        );
    }

    /**
     * Attempt to upgrade a vertical. Returns new state or null if cannot afford.
     */
    public function upgradeVertical(GameState $state, string $verticalId): ?GameState
    {
        $vertical = $this->config->findVertical($verticalId);
        if ($vertical === null) {
            return null;
        }

        $currentLevel = $state->verticals->getLevel($verticalId);
        $cost = $this->calculateVerticalUpgradeCost($verticalId, $currentLevel);

        if ($state->money < $cost) {
            return null; // Cannot afford
        }

        return new GameState(
            money: $state->money - $cost,
            totalVisitors: $state->totalVisitors,
            visitorsTowardsSale: $state->visitorsTowardsSale,
            totalSales: $state->totalSales,
            totalRevenue: $state->totalRevenue,
            buildings: $state->buildings,
            verticals: $state->verticals->withUpgrade($verticalId),
            timestamp: time(),
        );
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Initialize a new game state with starting conditions.
     *
     * @param int $startingMoney Starting money in centimes (default: 100€ = 10000)
     */
    public function initializeGameState(int $startingMoney = 10000): GameState
    {
        // Initialize verticals that start unlocked
        $verticalsData = [];
        foreach ($this->config->verticals as $vertical) {
            $verticalsData[$vertical->id] = $vertical->startsUnlocked() ? 1 : 0;
        }

        // Initialize buildings (all start at 0)
        $buildingsData = [];
        foreach ($this->config->marketing as $building) {
            $buildingsData[$building->id] = 0;
        }

        return new GameState(
            money: $startingMoney,
            totalVisitors: 0,
            visitorsTowardsSale: 0,
            totalSales: 0,
            totalRevenue: 0,
            buildings: new BuildingsState($buildingsData),
            verticals: new VerticalsState($verticalsData),
            timestamp: time(),
        );
    }

    /**
     * Get computed values for frontend display.
     *
     * @return array<string, mixed>
     */
    public function getComputedValues(GameState $state): array
    {
        $totalAttractivity = $this->calculateTotalAttractivity($state->verticals);
        $visitorsPerSecond = $this->calculateVisitorsPerSecond($state->buildings);

        // Calculate expected revenue per batch
        $expectedBuyers = $this->config->formulas->saleTriggerThreshold * $this->config->formulas->conversionRate;
        $expectedDistribution = $this->calculateRevenueDistribution($expectedBuyers, $state->verticals);
        $expectedRevenue = 0;
        foreach ($expectedDistribution as $item) {
            $expectedRevenue += $item['revenue'];
        }
        $expectedCommission = (int) floor($expectedRevenue * $this->config->formulas->baseCommissionRate);

        // Market distribution
        $marketDistribution = [];
        foreach ($this->config->verticals as $vertical) {
            $level = $state->verticals->getLevel($vertical->id);
            if ($level > 0 && $totalAttractivity > 0) {
                $marketDistribution[] = [
                    'id' => $vertical->id,
                    'name' => $vertical->name,
                    'icon' => $vertical->icon,
                    'marketShare' => ($vertical->attractivity / $totalAttractivity) * 100,
                    'level' => $level,
                    'currentPrice' => $this->calculateVerticalPrice($vertical->id, $level),
                ];
            }
        }

        // Building costs
        $buildingCosts = [];
        foreach ($this->config->marketing as $building) {
            $owned = $state->buildings->getOwned($building->id);
            $buildingCosts[$building->id] = [
                'cost' => $this->calculateBuildingCost($building->id, $owned),
                'canAfford' => $state->money >= $this->calculateBuildingCost($building->id, $owned),
            ];
        }

        // Vertical costs
        $verticalCosts = [];
        foreach ($this->config->verticals as $vertical) {
            $level = $state->verticals->getLevel($vertical->id);
            $cost = $this->calculateVerticalUpgradeCost($vertical->id, $level);
            $verticalCosts[$vertical->id] = [
                'cost' => $cost,
                'canAfford' => $state->money >= $cost,
                'currentPrice' => $this->calculateVerticalPrice($vertical->id, $level),
            ];
        }

        return [
            'totalAttractivity' => $totalAttractivity,
            'visitorsPerSecond' => $visitorsPerSecond,
            'expectedRevenuePerBatch' => $expectedCommission,
            'saleProgress' => ($state->visitorsTowardsSale / $this->config->formulas->saleTriggerThreshold) * 100,
            'unlockedVerticalsCount' => count($marketDistribution),
            'marketDistribution' => $marketDistribution,
            'buildingCosts' => $buildingCosts,
            'verticalCosts' => $verticalCosts,
        ];
    }
}
