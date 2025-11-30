/**
 * Game Configuration TypeScript Types
 *
 * These types mirror the PHP DTOs from src/Domain/Config/DTO/
 * All monetary values are stored in CENTIMES (1€ = 100 centimes).
 */

// =============================================================================
// FORMULAS CONFIG
// =============================================================================

/**
 * Game formulas and coefficients for all calculations.
 * Corresponds to: App\Domain\Config\DTO\FormulasConfigDTO
 */
export interface FormulasConfig {
    /** Building cost multiplier per purchase (e.g., 1.15 = +15% per owned) */
    costGrowthRate: number;

    /** Visitors gained per manual click */
    visitorsPerClick: number;

    /** Number of visitors needed to trigger a sale batch */
    saleTriggerThreshold: number;

    /** Percentage of visitors who become buyers (e.g., 0.10 = 10%) */
    conversionRate: number;

    /** Commission percentage on each sale (e.g., 0.10 = 10%) */
    baseCommissionRate: number;

    /** Vertical upgrade cost multiplier per level (e.g., 1.25 = +25%) */
    verticalUpgradeGrowthRate: number;

    /** Game tick interval in milliseconds */
    tickIntervalMs: number;
}

// =============================================================================
// BUILDING CONFIG
// =============================================================================

/**
 * Marketing building configuration.
 * Buildings generate passive visitors per second.
 * Corresponds to: App\Domain\Config\DTO\BuildingConfigDTO
 */
export interface BuildingConfig {
    /** Unique identifier (e.g., 'flyers', 'seo_basic') */
    id: string;

    /** Display name */
    name: string;

    /** Short description */
    description: string;

    /** Emoji icon for display */
    icon: string;

    /** Base cost in centimes */
    baseCost: number;

    /** Visitors generated per second */
    production: number;
}

// =============================================================================
// VERTICAL CONFIG
// =============================================================================

/**
 * Travel vertical (category) configuration.
 * Verticals represent travel categories with different price/attractivity ratios.
 * Corresponds to: App\Domain\Config\DTO\VerticalConfigDTO
 */
export interface VerticalConfig {
    /** Unique identifier (e.g., 'safari', 'weekend_france') */
    id: string;

    /** Display name */
    name: string;

    /** Short description */
    description: string;

    /** Emoji icon for display */
    icon: string;

    /** Base price in centimes (revenue per sale at level 1) */
    basePrice: number;

    /** Weight for market share calculation (higher = more buyers) */
    attractivity: number;

    /** Price multiplier per level (e.g., 1.07 = +7% per level) */
    marginGrowthFactor: number;

    /** Cost to unlock this vertical in centimes (0 = starts unlocked) */
    unlockCost: number;
}

// =============================================================================
// ROOT GAME CONFIG
// =============================================================================

/**
 * Root game configuration containing all settings.
 * This is the shape of data passed from Symfony to Svelte.
 * Corresponds to: App\Domain\Config\DTO\GameConfigDTO::toArray()
 */
export interface GameConfig {
    /** Game formulas and coefficients */
    formulas: FormulasConfig;

    /** Marketing buildings (passive visitors generators) */
    marketing: BuildingConfig[];

    /** Travel category verticals (revenue distribution) */
    verticals: VerticalConfig[];
}

// =============================================================================
// GAME STATE TYPES
// =============================================================================

/**
 * State of a single building owned by the player.
 */
export interface BuildingState {
    /** Number of buildings owned */
    owned: number;
}

/**
 * State of a single vertical for the player.
 */
export interface VerticalState {
    /** Current level (0 = locked, 1+ = unlocked) */
    level: number;
}

/**
 * Revenue breakdown for a single vertical in a sale batch.
 */
export interface VerticalRevenue {
    /** Vertical ID */
    id: string;

    /** Vertical name */
    name: string;

    /** Emoji icon */
    icon: string;

    /** Percentage of buyers (0-100) */
    marketShare: number;

    /** Number of sales (can be fractional) */
    sales: number;

    /** Revenue in centimes */
    revenue: number;

    /** Current price at level in centimes */
    currentPrice: number;
}

/**
 * Market distribution entry for UI display.
 */
export interface MarketDistributionEntry {
    /** Vertical ID */
    id: string;

    /** Vertical name */
    name: string;

    /** Emoji icon */
    icon: string;

    /** Market share percentage (0-100) */
    marketShare: number;

    /** Current level */
    level: number;

    /** Current price in centimes */
    currentPrice: number;
}

// =============================================================================
// API TYPES (Server Communication)
// =============================================================================

/**
 * Action types that can be sent to the server.
 */
export type GameActionType =
    | 'CLICK'
    | 'BUY_BUILDING'
    | 'UPGRADE_VERTICAL';

/**
 * Base interface for all game actions.
 */
export interface GameActionBase {
    /** Action type identifier */
    type: GameActionType;

    /** Timestamp when action was initiated */
    timestamp: number;
}

/**
 * Click action - player clicks to generate visitors.
 */
export interface ClickAction extends GameActionBase {
    type: 'CLICK';
    payload: {
        /** Number of clicks (for batch optimization) */
        count: number;
    };
}

/**
 * Buy building action - player purchases a marketing building.
 */
export interface BuyBuildingAction extends GameActionBase {
    type: 'BUY_BUILDING';
    payload: {
        /** Building ID to purchase */
        buildingId: string;
    };
}

/**
 * Upgrade vertical action - player unlocks or upgrades a vertical.
 */
export interface UpgradeVerticalAction extends GameActionBase {
    type: 'UPGRADE_VERTICAL';
    payload: {
        /** Vertical ID to upgrade */
        verticalId: string;
    };
}

/**
 * Union type for all possible game actions.
 */
export type GameAction = ClickAction | BuyBuildingAction | UpgradeVerticalAction;

/**
 * Pending action with snapshot for rollback.
 */
export interface PendingAction {
    /** The action to be synced */
    action: GameAction;

    /** Snapshot of state before action for rollback */
    snapshot: GameStateSnapshot;

    /** Unique ID for this pending action */
    id: string;
}

/**
 * Snapshot of game state for rollback purposes.
 */
export interface GameStateSnapshot {
    money: number;
    totalVisitors: number;
    visitorsTowardsSale: number;
    totalSales: number;
    totalRevenue: number;
    buildings: Record<string, BuildingState>;
    verticals: Record<string, VerticalState>;
}

/**
 * Request payload for syncing game actions with the server.
 */
export interface GameSyncRequest {
    /** Array of actions to process */
    actions: GameAction[];

    /** Client's current state hash for desync detection */
    stateHash?: string;
}

/**
 * Response from the server after processing sync request.
 */
export interface GameSyncResponse {
    /** Whether the sync was successful */
    success: boolean;

    /** Authoritative game state from server */
    state: ServerGameState;

    /** IDs of actions that were rejected */
    rejectedActionIds: string[];

    /** Error messages for rejected actions */
    errors: SyncError[];
}

/**
 * Error information for a rejected action.
 */
export interface SyncError {
    /** Action ID that was rejected */
    actionId?: string;

    /** Error code */
    code: string;

    /** Human-readable error message */
    message: string;
}

/**
 * Authoritative game state from server.
 */
export interface ServerGameState {
    /** Money in centimes */
    money: number;

    /** Total visitors accumulated */
    totalVisitors: number;

    /** Visitors counting towards next sale batch */
    visitorsTowardsSale: number;

    /** Total sales made */
    totalSales: number;

    /** Total revenue earned (before commission) */
    totalRevenue: number;

    /** Buildings state by ID */
    buildings: Record<string, BuildingState>;

    /** Verticals state by ID */
    verticals: Record<string, VerticalState>;

    /** Server timestamp for this state */
    timestamp: number;
}

/**
 * Initial state response when loading the game.
 */
export interface GameInitResponse {
    /** Game configuration */
    config: GameConfig;

    /** Current game state (or default for new games) */
    state: ServerGameState;
}
