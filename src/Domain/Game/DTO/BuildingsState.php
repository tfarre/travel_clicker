<?php

declare(strict_types=1);

namespace App\Domain\Game\DTO;

/**
 * Immutable state of a player's buildings.
 *
 * @template-implements \ArrayAccess<string, int>
 */
readonly class BuildingsState implements \ArrayAccess, \JsonSerializable
{
    /**
     * @param array<string, int> $owned Building ID => quantity owned
     */
    public function __construct(
        private array $owned = [],
    ) {
    }

    public function getOwned(string $buildingId): int
    {
        return $this->owned[$buildingId] ?? 0;
    }

    public function withPurchase(string $buildingId): self
    {
        $newOwned = $this->owned;
        $newOwned[$buildingId] = ($newOwned[$buildingId] ?? 0) + 1;

        return new self($newOwned);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->owned[$offset]);
    }

    public function offsetGet(mixed $offset): int
    {
        return $this->getOwned($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('BuildingsState is immutable');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('BuildingsState is immutable');
    }

    /**
     * @return array<string, array{owned: int}>
     */
    public function jsonSerialize(): array
    {
        $result = [];
        foreach ($this->owned as $id => $qty) {
            $result[$id] = ['owned' => $qty];
        }

        return $result;
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return $this->owned;
    }
}
