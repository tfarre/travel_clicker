<?php

declare(strict_types=1);

namespace App\Domain\Config\DTO;

/**
 * Immutable Data Transfer Object for a single building configuration.
 *
 * Buildings can be either:
 * - Marketing: generates passive visitors (has production value)
 * - Partner: increases cart value (has cartBonus value)
 */
readonly class BuildingConfigDTO
{
    /**
     * @param string      $id          Unique identifier (e.g., 'flyers', 'hotel_3_stars')
     * @param string      $name        Display name
     * @param string      $description Short description of the building
     * @param string      $icon        Emoji icon for display
     * @param int         $baseCost    Base cost in centimes
     * @param string      $type        Building type: 'marketing' or 'partner'
     * @param float|null  $production  Visitors per second (marketing buildings only)
     * @param int|null    $cartBonus   Cart value bonus in centimes (partner buildings only)
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $icon,
        public int $baseCost,
        public string $type,
        public ?float $production = null,
        public ?int $cartBonus = null,
    ) {
    }

    /**
     * Check if this is a marketing building.
     */
    public function isMarketing(): bool
    {
        return $this->type === 'marketing';
    }

    /**
     * Check if this is a partner building.
     */
    public function isPartner(): bool
    {
        return $this->type === 'partner';
    }
}
