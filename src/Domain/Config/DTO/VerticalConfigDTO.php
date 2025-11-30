<?php

declare(strict_types=1);

namespace App\Domain\Config\DTO;

/**
 * Immutable Data Transfer Object for a Vertical (travel category) configuration.
 *
 * A Vertical represents a travel category (e.g., Hiking, Surf, Safari).
 * Revenue is distributed among unlocked verticals based on their attractivity weight.
 *
 * The revenue model follows an "entonnoir" (funnel):
 * 1. Buyers are distributed among verticals by market share (attractivity / total)
 * 2. Each vertical's price increases with level (basePrice × marginGrowthFactor^level)
 * 3. Total revenue = sum of (sales × currentPrice) for each vertical
 */
readonly class VerticalConfigDTO
{
    /**
     * @param string $id                  Unique identifier (e.g., 'safari', 'randonnee')
     * @param string $name                Display name
     * @param string $description         Short description
     * @param string $icon                Emoji icon for display
     * @param int    $basePrice           Base price in centimes (revenue per sale at level 1)
     * @param int    $attractivity        Weight for market share calculation (higher = more buyers)
     * @param float  $marginGrowthFactor  Price multiplier per level (e.g., 1.07 = +7%)
     * @param int    $unlockCost          Cost to unlock this vertical (0 = starts unlocked)
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $icon,
        public int $basePrice,
        public int $attractivity,
        public float $marginGrowthFactor,
        public int $unlockCost,
    ) {
    }

    /**
     * Check if this vertical starts unlocked (free to unlock).
     */
    public function startsUnlocked(): bool
    {
        return $this->unlockCost === 0;
    }

    /**
     * Calculate the current price at a given level.
     *
     * Formula: basePrice × (marginGrowthFactor ^ (level - 1))
     *
     * @param int $level Current level (1+)
     *
     * @return int Current price in centimes
     */
    public function calculatePriceAtLevel(int $level): int
    {
        if ($level < 1) {
            return 0; // Not unlocked
        }

        return (int) floor($this->basePrice * ($this->marginGrowthFactor ** ($level - 1)));
    }
}
