<?php

declare(strict_types=1);

namespace App\UserInterface\Controller\Api;

use App\Domain\Config\GameConfigLoader;
use App\Domain\Game\DTO\BuildingsState;
use App\Domain\Game\DTO\GameState;
use App\Domain\Game\DTO\VerticalsState;
use App\Domain\Game\Service\GameCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Game API Controller - Server-Authoritative Game Endpoints
 *
 * Handles all game state mutations. The frontend sends batched actions,
 * this controller processes them and returns the authoritative state.
 *
 * For MVP, we use session storage. In Phase 3, this will be PostgreSQL.
 */
#[Route('/api/game', name: 'api_game_')]
final class GameApiController extends AbstractController
{
    private const SESSION_KEY = 'game_state';

    public function __construct(
        private readonly GameConfigLoader $configLoader,
    ) {
    }

    /**
     * Get the current game state.
     *
     * Returns the authoritative server state plus computed values for UI.
     */
    #[Route('/state', name: 'state', methods: ['GET'])]
    public function getState(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $config = $this->configLoader->load();
        $calculator = new GameCalculator($config);

        $state = $this->loadOrCreateState($session, $calculator);
        $computed = $calculator->getComputedValues($state);

        return $this->json([
            'success' => true,
            'state' => $state,
            'computed' => $computed,
            'config' => $config->toArray(),
        ]);
    }

    /**
     * Sync game actions from the client.
     *
     * Processes a batch of actions and returns the new authoritative state.
     * Actions that fail (e.g., insufficient funds) are rejected.
     */
    #[Route('/sync', name: 'sync', methods: ['POST'])]
    public function sync(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $config = $this->configLoader->load();
        $calculator = new GameCalculator($config);

        $state = $this->loadOrCreateState($session, $calculator);

        // Parse request body
        $data = json_decode($request->getContent(), true);
        $actions = $data['actions'] ?? [];

        $rejectedActionIds = [];
        $errors = [];

        foreach ($actions as $action) {
            $actionId = $action['id'] ?? null;
            $type = $action['type'] ?? '';
            $payload = $action['payload'] ?? [];

            $newState = match ($type) {
                'CLICK' => $this->handleClick($calculator, $state, $payload),
                'BUY_BUILDING' => $this->handleBuyBuilding($calculator, $state, $payload),
                'UPGRADE_VERTICAL' => $this->handleUpgradeVertical($calculator, $state, $payload),
                default => null,
            };

            if ($newState === null) {
                // Action was rejected
                if ($actionId !== null) {
                    $rejectedActionIds[] = $actionId;
                    $errors[] = [
                        'actionId' => $actionId,
                        'code' => 'ACTION_REJECTED',
                        'message' => $this->getErrorMessage($type, $payload),
                    ];
                }
            } else {
                $state = $newState;
            }
        }

        // Save the new state
        $this->saveState($session, $state);

        $computed = $calculator->getComputedValues($state);

        return $this->json([
            'success' => count($rejectedActionIds) === 0,
            'state' => $state,
            'computed' => $computed,
            'rejectedActionIds' => $rejectedActionIds,
            'errors' => $errors,
        ]);
    }

    /**
     * Process passive income tick.
     *
     * Called periodically to add visitors based on elapsed time.
     */
    #[Route('/tick', name: 'tick', methods: ['POST'])]
    public function tick(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $config = $this->configLoader->load();
        $calculator = new GameCalculator($config);

        $state = $this->loadOrCreateState($session, $calculator);

        $data = json_decode($request->getContent(), true);
        $elapsedMs = (int) ($data['elapsedMs'] ?? 0);

        if ($elapsedMs > 0 && $elapsedMs <= 10000) { // Cap at 10 seconds to prevent cheating
            $state = $calculator->processTick($state, $elapsedMs);
            $this->saveState($session, $state);
        }

        $computed = $calculator->getComputedValues($state);

        return $this->json([
            'success' => true,
            'state' => $state,
            'computed' => $computed,
        ]);
    }

    /**
     * Reset the game state (for testing/debug).
     */
    #[Route('/reset', name: 'reset', methods: ['POST'])]
    public function reset(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $config = $this->configLoader->load();
        $calculator = new GameCalculator($config);

        $state = $calculator->initializeGameState();
        $this->saveState($session, $state);

        $computed = $calculator->getComputedValues($state);

        return $this->json([
            'success' => true,
            'state' => $state,
            'computed' => $computed,
            'config' => $config->toArray(),
        ]);
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    private function handleClick(GameCalculator $calculator, GameState $state, array $payload): GameState
    {
        $clicks = max(1, min(100, (int) ($payload['count'] ?? 1))); // Cap at 100 clicks per batch

        return $calculator->processClick($state, $clicks);
    }

    private function handleBuyBuilding(GameCalculator $calculator, GameState $state, array $payload): ?GameState
    {
        $buildingId = $payload['buildingId'] ?? '';

        return $calculator->buyBuilding($state, $buildingId);
    }

    private function handleUpgradeVertical(GameCalculator $calculator, GameState $state, array $payload): ?GameState
    {
        $verticalId = $payload['verticalId'] ?? '';

        return $calculator->upgradeVertical($state, $verticalId);
    }

    private function loadOrCreateState(SessionInterface $session, GameCalculator $calculator): GameState
    {
        $data = $session->get(self::SESSION_KEY);

        if ($data === null) {
            return $calculator->initializeGameState();
        }

        // Reconstruct state from session data
        return new GameState(
            money: (int) $data['money'],
            totalVisitors: (int) $data['totalVisitors'],
            visitorsTowardsSale: (float) $data['visitorsTowardsSale'],
            totalSales: (float) $data['totalSales'],
            totalRevenue: (int) $data['totalRevenue'],
            buildings: new BuildingsState($this->extractBuildingsFromSession($data['buildings'] ?? [])),
            verticals: new VerticalsState($this->extractVerticalsFromSession($data['verticals'] ?? [])),
            timestamp: (int) ($data['timestamp'] ?? time()),
        );
    }

    /**
     * @param array<string, array{owned: int}> $buildings
     * @return array<string, int>
     */
    private function extractBuildingsFromSession(array $buildings): array
    {
        $result = [];
        foreach ($buildings as $id => $data) {
            $result[$id] = $data['owned'] ?? 0;
        }

        return $result;
    }

    /**
     * @param array<string, array{level: int}> $verticals
     * @return array<string, int>
     */
    private function extractVerticalsFromSession(array $verticals): array
    {
        $result = [];
        foreach ($verticals as $id => $data) {
            $result[$id] = $data['level'] ?? 0;
        }

        return $result;
    }

    private function saveState(SessionInterface $session, GameState $state): void
    {
        $session->set(self::SESSION_KEY, $state->jsonSerialize());
    }

    private function getErrorMessage(string $type, array $payload): string
    {
        return match ($type) {
            'BUY_BUILDING' => sprintf('Cannot purchase building: %s (insufficient funds)', $payload['buildingId'] ?? 'unknown'),
            'UPGRADE_VERTICAL' => sprintf('Cannot upgrade vertical: %s (insufficient funds)', $payload['verticalId'] ?? 'unknown'),
            default => 'Unknown action or invalid payload',
        };
    }
}
