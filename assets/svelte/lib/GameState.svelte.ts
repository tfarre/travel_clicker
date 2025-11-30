/**
 * GameState - Global Reactive State with Server-Authoritative Pattern
 *
 * This module implements the Optimistic UI pattern:
 * 1. User action triggers immediate UI update (optimistic)
 * 2. Action is queued for server sync
 * 3. Server validates and responds with authoritative state
 * 4. If rejected, rollback to snapshot
 *
 * All monetary values are stored in CENTIMES (1€ = 100 centimes).
 */

import type {
    BuildingConfig,
    BuildingState,
    GameAction,
    GameConfig,
    GameStateSnapshot,
    MarketDistributionEntry,
    PendingAction,
    ServerGameState,
    VerticalConfig,
    VerticalState,
} from '../types/game';
import { fetchGameState, syncActions, sendTick } from './api';

// Generate unique IDs for pending actions
let actionIdCounter = 0;
function generateActionId(): string {
    return `action_${Date.now()}_${++actionIdCounter}`;
}

/**
 * Computed values from server (calculated by GameCalculator on backend)
 */
interface ComputedValues {
    totalAttractivity: number;
    visitorsPerSecond: number;
    expectedRevenuePerBatch: number;
    saleProgress: number;
    unlockedVerticalsCount: number;
    marketDistribution: MarketDistributionEntry[];
    buildingCosts: Record<string, { cost: number; canAfford: boolean }>;
    verticalCosts: Record<string, { cost: number; canAfford: boolean; currentPrice: number }>;
}

/**
 * GameState Class - Manages all game state with Svelte 5 Runes
 */
export class GameState {
    // =========================================================================
    // Core State (Svelte 5 $state runes)
    // =========================================================================

    /** Game configuration from server */
    config = $state<GameConfig | null>(null);

    /** Money in centimes */
    money = $state(0);

    /** Total visitors accumulated */
    totalVisitors = $state(0);

    /** Visitors counting towards next sale batch */
    visitorsTowardsSale = $state(0);

    /** Total sales made */
    totalSales = $state(0);

    /** Total revenue earned */
    totalRevenue = $state(0);

    /** Buildings state by ID */
    buildings = $state<Record<string, BuildingState>>({});

    /** Verticals state by ID */
    verticals = $state<Record<string, VerticalState>>({});

    /** Server-computed values */
    computed = $state<ComputedValues | null>(null);

    /** Whether we're currently syncing with server */
    isSyncing = $state(false);

    /** Whether initial load is complete */
    isInitialized = $state(false);

    /** Error message to display */
    errorMessage = $state<string | null>(null);

    // =========================================================================
    // Pending Actions Queue (for Optimistic UI)
    // =========================================================================

    #pendingActions: PendingAction[] = [];
    #syncTimeout: ReturnType<typeof setTimeout> | null = null;
    #lastTickTime: number = Date.now();

    // Sync configuration
    readonly #SYNC_DEBOUNCE_MS = 500;
    readonly #TICK_INTERVAL_MS = 1000;

    // =========================================================================
    // Derived State (Svelte 5 $derived)
    // =========================================================================

    /** Formatted money display */
    moneyFormatted = $derived(this.formatMoney(this.money));

    /** Visitors per second */
    visitorsPerSecond = $derived(this.computed?.visitorsPerSecond ?? 0);

    /** Sale progress percentage */
    saleProgress = $derived(this.computed?.saleProgress ?? 0);

    /** Total attractivity */
    totalAttractivity = $derived(this.computed?.totalAttractivity ?? 0);

    /** Unlocked verticals count */
    unlockedVerticalsCount = $derived(this.computed?.unlockedVerticalsCount ?? 0);

    /** Market distribution */
    marketDistribution = $derived(this.computed?.marketDistribution ?? []);

    /** Expected revenue per batch */
    expectedRevenuePerBatch = $derived(this.computed?.expectedRevenuePerBatch ?? 0);

    // =========================================================================
    // Initialization
    // =========================================================================

    /**
     * Initialize game state from server.
     * Call this when the component mounts.
     */
    async init(initialConfig?: GameConfig): Promise<void> {
        try {
            // If config was provided via Twig, use it but still fetch state
            if (initialConfig) {
                this.config = initialConfig;
            }

            const response = await fetchGameState();

            this.config = response.config ?? this.config;
            this.#applyServerState(response.state);
            this.computed = response.computed;
            this.isInitialized = true;

            // Start tick loop
            this.#startTickLoop();
        } catch (error) {
            console.error('Failed to initialize game:', error);
            this.errorMessage = 'Erreur de connexion au serveur';

            // Fallback: use config from Twig if available
            if (initialConfig && !this.isInitialized) {
                this.config = initialConfig;
                this.#initializeDefaultState();
                this.isInitialized = true;
            }
        }
    }

    /**
     * Initialize default state when server is unavailable.
     */
    #initializeDefaultState(): void {
        if (!this.config) return;

        this.money = 10000; // 100€

        const buildingsState: Record<string, BuildingState> = {};
        for (const building of this.config.marketing) {
            buildingsState[building.id] = { owned: 0 };
        }
        this.buildings = buildingsState;

        const verticalsState: Record<string, VerticalState> = {};
        for (const vertical of this.config.verticals) {
            verticalsState[vertical.id] = {
                level: vertical.unlockCost === 0 ? 1 : 0
            };
        }
        this.verticals = verticalsState;
    }

    // =========================================================================
    // Actions (Optimistic UI)
    // =========================================================================

    /**
     * Handle a manual click to generate visitors.
     */
    click(): void {
        if (!this.config) return;

        // Optimistic update
        const visitors = this.config.formulas.visitorsPerClick;
        this.totalVisitors += visitors;
        this.visitorsTowardsSale += visitors;

        // Check for sale trigger (optimistic)
        this.#processOptimisticSales();

        // Queue action for sync
        this.#queueAction({
            type: 'CLICK',
            timestamp: Date.now(),
            payload: { count: 1 },
        });
    }

    /**
     * Attempt to buy a marketing building.
     */
    buyBuilding(buildingId: string): boolean {
        if (!this.config) return false;

        const building = this.config.marketing.find(b => b.id === buildingId);
        if (!building) return false;

        const cost = this.getBuildingCost(buildingId);
        if (this.money < cost) return false;

        // Optimistic update
        this.money -= cost;
        if (this.buildings[buildingId]) {
            this.buildings[buildingId].owned += 1;
        }

        // Queue action for sync
        this.#queueAction({
            type: 'BUY_BUILDING',
            timestamp: Date.now(),
            payload: { buildingId },
        });

        return true;
    }

    /**
     * Attempt to upgrade a vertical.
     */
    upgradeVertical(verticalId: string): boolean {
        if (!this.config) return false;

        const vertical = this.config.verticals.find(v => v.id === verticalId);
        if (!vertical) return false;

        const cost = this.getVerticalUpgradeCost(verticalId);
        if (this.money < cost) return false;

        // Optimistic update
        this.money -= cost;
        if (this.verticals[verticalId]) {
            this.verticals[verticalId].level += 1;
        }

        // Queue action for sync
        this.#queueAction({
            type: 'UPGRADE_VERTICAL',
            timestamp: Date.now(),
            payload: { verticalId },
        });

        return true;
    }

    // =========================================================================
    // Server Sync
    // =========================================================================

    /**
     * Queue an action for server sync.
     */
    #queueAction(action: GameAction): void {
        const snapshot = this.#createSnapshot();

        this.#pendingActions.push({
            action: { ...action, id: generateActionId() } as GameAction & { id: string },
            snapshot,
            id: generateActionId(),
        });

        // Debounce sync
        this.#scheduleSyncWithDebounce();
    }

    /**
     * Schedule a sync with debouncing.
     */
    #scheduleSyncWithDebounce(): void {
        if (this.#syncTimeout) {
            clearTimeout(this.#syncTimeout);
        }

        this.#syncTimeout = setTimeout(() => {
            this.#performSync();
        }, this.#SYNC_DEBOUNCE_MS);
    }

    /**
     * Perform sync with server.
     */
    async #performSync(): Promise<void> {
        if (this.#pendingActions.length === 0) return;
        if (this.isSyncing) return;

        this.isSyncing = true;

        const actionsToSync = [...this.#pendingActions];
        this.#pendingActions = [];

        try {
            const response = await syncActions(
                actionsToSync.map(pa => pa.action)
            );

            if (response.rejectedActionIds.length > 0) {
                // Some actions were rejected - show error
                this.errorMessage = 'Action impossible - état resynchronisé';

                // Clear error after 3 seconds
                setTimeout(() => {
                    this.errorMessage = null;
                }, 3000);
            }

            // Apply authoritative state from server
            this.#applyServerState(response.state);
            this.computed = response.computed;

        } catch (error) {
            console.error('Sync failed:', error);
            this.errorMessage = 'Erreur de synchronisation';

            // Rollback all pending actions
            if (actionsToSync.length > 0) {
                const firstSnapshot = actionsToSync[0].snapshot;
                this.#applySnapshot(firstSnapshot);
            }

            setTimeout(() => {
                this.errorMessage = null;
            }, 3000);
        } finally {
            this.isSyncing = false;
        }
    }

    /**
     * Create a snapshot of current state for rollback.
     */
    #createSnapshot(): GameStateSnapshot {
        return {
            money: this.money,
            totalVisitors: this.totalVisitors,
            visitorsTowardsSale: this.visitorsTowardsSale,
            totalSales: this.totalSales,
            totalRevenue: this.totalRevenue,
            buildings: JSON.parse(JSON.stringify(this.buildings)),
            verticals: JSON.parse(JSON.stringify(this.verticals)),
        };
    }

    /**
     * Apply a snapshot (for rollback).
     */
    #applySnapshot(snapshot: GameStateSnapshot): void {
        this.money = snapshot.money;
        this.totalVisitors = snapshot.totalVisitors;
        this.visitorsTowardsSale = snapshot.visitorsTowardsSale;
        this.totalSales = snapshot.totalSales;
        this.totalRevenue = snapshot.totalRevenue;
        this.buildings = snapshot.buildings;
        this.verticals = snapshot.verticals;
    }

    /**
     * Apply authoritative state from server.
     */
    #applyServerState(state: ServerGameState): void {
        this.money = state.money;
        this.totalVisitors = state.totalVisitors;
        this.visitorsTowardsSale = state.visitorsTowardsSale;
        this.totalSales = state.totalSales;
        this.totalRevenue = state.totalRevenue;
        this.buildings = state.buildings;
        this.verticals = state.verticals;
    }

    // =========================================================================
    // Tick Loop (Passive Income)
    // =========================================================================

    #tickInterval: ReturnType<typeof setInterval> | null = null;

    #startTickLoop(): void {
        this.#lastTickTime = Date.now();

        this.#tickInterval = setInterval(async () => {
            const now = Date.now();
            const elapsed = now - this.#lastTickTime;
            this.#lastTickTime = now;

            if (elapsed > 0 && this.visitorsPerSecond > 0) {
                try {
                    const response = await sendTick(elapsed);
                    this.#applyServerState(response.state);
                    this.computed = response.computed;
                } catch (error) {
                    // Silently fail ticks - will resync later
                    console.warn('Tick failed:', error);
                }
            }
        }, this.#TICK_INTERVAL_MS);
    }

    /**
     * Stop the tick loop (call on unmount).
     */
    destroy(): void {
        if (this.#tickInterval) {
            clearInterval(this.#tickInterval);
            this.#tickInterval = null;
        }
        if (this.#syncTimeout) {
            clearTimeout(this.#syncTimeout);
            this.#syncTimeout = null;
        }
    }

    // =========================================================================
    // Cost & Price Calculations (using server-computed values)
    // =========================================================================

    getBuildingCost(buildingId: string): number {
        return this.computed?.buildingCosts[buildingId]?.cost ?? 0;
    }

    canAffordBuilding(buildingId: string): boolean {
        return this.computed?.buildingCosts[buildingId]?.canAfford ?? false;
    }

    getVerticalUpgradeCost(verticalId: string): number {
        return this.computed?.verticalCosts[verticalId]?.cost ?? 0;
    }

    canAffordVertical(verticalId: string): boolean {
        return this.computed?.verticalCosts[verticalId]?.canAfford ?? false;
    }

    getVerticalCurrentPrice(verticalId: string): number {
        return this.computed?.verticalCosts[verticalId]?.currentPrice ?? 0;
    }

    // =========================================================================
    // Optimistic Calculations (for immediate UI feedback)
    // =========================================================================

    /**
     * Process optimistic sales when threshold is reached.
     */
    #processOptimisticSales(): void {
        if (!this.config) return;

        const threshold = this.config.formulas.saleTriggerThreshold;

        while (this.visitorsTowardsSale >= threshold) {
            this.visitorsTowardsSale -= threshold;
            // Note: We don't calculate revenue optimistically
            // The server will provide the accurate values
        }
    }

    // =========================================================================
    // Utility Methods
    // =========================================================================

    /**
     * Format centimes as euros.
     */
    formatMoney(centimes: number): string {
        const euros = centimes / 100;
        return euros.toLocaleString('fr-FR', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

// Create singleton instance
export const gameState = new GameState();
