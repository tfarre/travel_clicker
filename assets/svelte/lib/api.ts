/**
 * API Client for Game Server Communication
 *
 * Handles all HTTP communication with the Symfony backend.
 */

import type {
    GameAction,
    GameConfig,
    GameSyncRequest,
    GameSyncResponse,
    ServerGameState,
} from '../types/game';

interface ComputedValues {
    totalAttractivity: number;
    visitorsPerSecond: number;
    expectedRevenuePerBatch: number;
    saleProgress: number;
    unlockedVerticalsCount: number;
    marketDistribution: Array<{
        id: string;
        name: string;
        icon: string;
        marketShare: number;
        level: number;
        currentPrice: number;
    }>;
    buildingCosts: Record<string, { cost: number; canAfford: boolean }>;
    verticalCosts: Record<string, { cost: number; canAfford: boolean; currentPrice: number }>;
}

interface StateResponse {
    success: boolean;
    state: ServerGameState;
    computed: ComputedValues;
    config?: GameConfig;
}

interface TickResponse {
    success: boolean;
    state: ServerGameState;
    computed: ComputedValues;
}

const API_BASE = '/api/game';

/**
 * Fetch the current game state from the server.
 */
export async function fetchGameState(): Promise<StateResponse> {
    const response = await fetch(`${API_BASE}/state`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Failed to fetch game state: ${response.status}`);
    }

    return response.json();
}

/**
 * Sync a batch of actions with the server.
 */
export async function syncActions(actions: GameAction[]): Promise<GameSyncResponse & { computed: ComputedValues }> {
    const request: GameSyncRequest = { actions };

    const response = await fetch(`${API_BASE}/sync`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify(request),
    });

    if (!response.ok) {
        throw new Error(`Failed to sync actions: ${response.status}`);
    }

    return response.json();
}

/**
 * Send a tick request for passive income.
 */
export async function sendTick(elapsedMs: number): Promise<TickResponse> {
    const response = await fetch(`${API_BASE}/tick`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ elapsedMs }),
    });

    if (!response.ok) {
        throw new Error(`Failed to send tick: ${response.status}`);
    }

    return response.json();
}

/**
 * Reset the game state.
 */
export async function resetGame(): Promise<StateResponse> {
    const response = await fetch(`${API_BASE}/reset`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Failed to reset game: ${response.status}`);
    }

    return response.json();
}
