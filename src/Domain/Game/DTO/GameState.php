<?php

declare(strict_types=1);

namespace App\Domain\Game\DTO;

/**
 * Complete game state for a player.
 *
 * This DTO represents the authoritative server state that will be serialized
 * and sent to the frontend. All monetary values are in centimes.
 */
readonly class GameState implements \JsonSerializable
{
    public function __construct(
        public int $money,
        public int $totalVisitors,
        public float $visitorsTowardsSale,
        public float $totalSales,
        public int $totalRevenue,
        public BuildingsState $buildings,
        public VerticalsState $verticals,
        public int $timestamp,
    ) {
    }

    /**
     * Create a new game state with starting values.
     */
    public static function createNew(int $startingMoney = 10000): self
    {
        return new self(
            money: $startingMoney,
            totalVisitors: 0,
            visitorsTowardsSale: 0,
            totalSales: 0,
            totalRevenue: 0,
            buildings: new BuildingsState(),
            verticals: new VerticalsState(),
            timestamp: time(),
        );
    }

    public function withMoney(int $money): self
    {
        return new self(
            money: $money,
            totalVisitors: $this->totalVisitors,
            visitorsTowardsSale: $this->visitorsTowardsSale,
            totalSales: $this->totalSales,
            totalRevenue: $this->totalRevenue,
            buildings: $this->buildings,
            verticals: $this->verticals,
            timestamp: time(),
        );
    }

    public function withBuildings(BuildingsState $buildings): self
    {
        return new self(
            money: $this->money,
            totalVisitors: $this->totalVisitors,
            visitorsTowardsSale: $this->visitorsTowardsSale,
            totalSales: $this->totalSales,
            totalRevenue: $this->totalRevenue,
            buildings: $buildings,
            verticals: $this->verticals,
            timestamp: time(),
        );
    }

    public function withVerticals(VerticalsState $verticals): self
    {
        return new self(
            money: $this->money,
            totalVisitors: $this->totalVisitors,
            visitorsTowardsSale: $this->visitorsTowardsSale,
            totalSales: $this->totalSales,
            totalRevenue: $this->totalRevenue,
            buildings: $this->buildings,
            verticals: $verticals,
            timestamp: time(),
        );
    }

    public function withVisitors(int $totalVisitors, float $visitorsTowardsSale): self
    {
        return new self(
            money: $this->money,
            totalVisitors: $totalVisitors,
            visitorsTowardsSale: $visitorsTowardsSale,
            totalSales: $this->totalSales,
            totalRevenue: $this->totalRevenue,
            buildings: $this->buildings,
            verticals: $this->verticals,
            timestamp: time(),
        );
    }

    public function withSales(float $totalSales, int $totalRevenue, int $money): self
    {
        return new self(
            money: $money,
            totalVisitors: $this->totalVisitors,
            visitorsTowardsSale: $this->visitorsTowardsSale,
            totalSales: $totalSales,
            totalRevenue: $totalRevenue,
            buildings: $this->buildings,
            verticals: $this->verticals,
            timestamp: time(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'money' => $this->money,
            'totalVisitors' => $this->totalVisitors,
            'visitorsTowardsSale' => $this->visitorsTowardsSale,
            'totalSales' => $this->totalSales,
            'totalRevenue' => $this->totalRevenue,
            'buildings' => $this->buildings->jsonSerialize(),
            'verticals' => $this->verticals->jsonSerialize(),
            'timestamp' => $this->timestamp,
        ];
    }
}
