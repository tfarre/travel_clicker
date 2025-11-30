<?php

declare(strict_types=1);

namespace App\Domain\Config\DTO;

/**
 * Immutable Data Transfer Object for a marketing building configuration.
 *
 * Marketing buildings generate passive visitors per second.
 * All monetary values are stored in CENTIMES (1€ = 100 centimes).
 */
readonly class BuildingConfigDTO
{
    /**
     * @param string $id          Unique identifier (e.g., 'flyers', 'seo_basic')
     * @param string $name        Display name
     * @param string $description Short description of the building
     * @param string $icon        Emoji icon for display
     * @param int    $baseCost    Base cost in centimes
     * @param float  $production  Visitors generated per second
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $icon,
        public int $baseCost,
        public float $production,
    ) {
    }
}
