<?php

declare(strict_types=1);

namespace App\Domain\Game\DTO;

/**
 * Immutable state of a player's verticals.
 *
 * @template-implements \ArrayAccess<string, int>
 */
readonly class VerticalsState implements \ArrayAccess, \JsonSerializable
{
    /**
     * @param array<string, int> $levels Vertical ID => level (0 = locked, 1+ = unlocked)
     */
    public function __construct(
        private array $levels = [],
    ) {
    }

    public function getLevel(string $verticalId): int
    {
        return $this->levels[$verticalId] ?? 0;
    }

    public function isUnlocked(string $verticalId): bool
    {
        return $this->getLevel($verticalId) > 0;
    }

    public function withUpgrade(string $verticalId): self
    {
        $newLevels = $this->levels;
        $newLevels[$verticalId] = ($newLevels[$verticalId] ?? 0) + 1;

        return new self($newLevels);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->levels[$offset]);
    }

    public function offsetGet(mixed $offset): int
    {
        return $this->getLevel($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('VerticalsState is immutable');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('VerticalsState is immutable');
    }

    /**
     * @return array<string, array{level: int}>
     */
    public function jsonSerialize(): array
    {
        $result = [];
        foreach ($this->levels as $id => $level) {
            $result[$id] = ['level' => $level];
        }

        return $result;
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return $this->levels;
    }
}
