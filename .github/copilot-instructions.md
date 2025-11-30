# Travel Clicker - Technical Guidelines & Architecture

## Mission & Persona

You are the Lead Architect and Developer of **Travel Clicker**, a scalable marketplace simulation game. Your goal is to build a robust, "Enterprise-grade" game architecture using **Symfony 7.4**, **PHP 8.4**, and **Svelte 5**.

Unlike standard CRUD applications, this project handles high-frequency user actions via a **Server-Authoritative model** with real-time capabilities. The code must be performant, strictly typed, and designed to accommodate future scaling.

---

## General Principles

| Principle | Description |
|-----------|-------------|
| **Server Authority** | The Frontend is a projection. The Backend (Symfony) holds the absolute truth. Never trust client-side calculations for critical business logic (money, XP). |
| **Optimistic UI** | The Frontend must react instantly to user actions (prediction) while syncing with the server in the background. |
| **Real-Time Feedback** | Use Server-Sent Events (Mercure) to push state updates (leaderboards, passive income) to the client without polling. |
| **SOLID & DDD** | Business logic (Maths, ROI calculations, Unlock rules) belongs strictly in the `/Domain` layer. |
| **Batch Processing** | Client actions are aggregated and sent in batches to preserve server resources. |

---

## Optimistic UI & Error Handling

### The Challenge

With Optimistic UI, the frontend assumes success and updates immediately. But what happens when the server says **"NO"** (insufficient funds, invalid action, desync)?

### Rollback Strategy

The frontend maintains a **pending actions queue** with snapshots for rollback:

```
┌─────────────────────────────────────────────────────────────┐
│  1. User clicks "Buy Building"                              │
│     └── Frontend: Optimistically deduct money, add building │
│     └── Queue: { action: 'buy', snapshot: previousState }   │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  2. Batch sent to server (every 500ms or on threshold)      │
└─────────────────────────────────────────────────────────────┘
                            │
              ┌─────────────┴─────────────┐
              ▼                           ▼
┌──────────────────────┐    ┌──────────────────────────────┐
│  3a. Server: OK ✅   │    │  3b. Server: REJECTED ❌     │
│  └── Clear snapshot  │    │  └── Rollback to snapshot    │
│  └── Confirm state   │    │  └── Show toast notification │
└──────────────────────┘    └──────────────────────────────┘
```

### Implementation Pattern

```javascript
// GameState.svelte.js
class GameState {
    #pendingActions = $state([]);
    money = $state(0);
    
    buyBuilding(buildingId) {
        const snapshot = this.#createSnapshot();
        const cost = this.#calculateCost(buildingId);
        
        // Optimistic update
        this.money -= cost;
        this.buildings[buildingId].owned++;
        
        // Queue for sync
        this.#pendingActions.push({
            type: 'BUY_BUILDING',
            payload: { buildingId },
            snapshot,
            timestamp: Date.now()
        });
    }
    
    handleServerResponse(response) {
        if (response.rejected.length > 0) {
            // Rollback rejected actions
            this.#rollback(response.rejected);
            this.#showErrorToast('Action impossible - état resynchronisé');
        }
        // Apply authoritative state from server
        this.#applyServerState(response.state);
    }
}
```

### Error UX Guidelines

| Scenario | User Feedback |
|----------|---------------|
| **Soft rejection** (not enough money) | Toast: "Fonds insuffisants" + subtle shake animation |
| **Desync detected** | Toast: "Resynchronisation..." + brief loading overlay |
| **Network error** | Toast: "Connexion perdue - mode hors-ligne" + retry queue |
| **Critical error** | Modal: "Erreur critique" + force refresh option |

### Key Rules

- **Never block the UI** waiting for server response
- **Always have a rollback path** for every optimistic action
- **Silent resync** for minor discrepancies (< 1% difference)
- **Explicit notification** for user-impacting rollbacks
- **Server state wins** in case of conflict (Server Authority principle)

---

## Game Configuration Architecture

### Config-Driven Design

The game follows a **Config-Driven Architecture** where Symfony is the single source of truth for all game rules, formulas, and balancing.

#### Core Concept

| Component | Role |
|-----------|------|
| **Symfony (Backend)** | Defines all rules (prices, coefficients, formulas) in YAML or PHP config |
| **Config Injection** | At page load, Symfony serializes the config as JSON and injects it into Twig |
| **Svelte (Frontend)** | Acts as a **Generic Engine** - no hardcoded values, only applies rules from config |

#### Why This Approach?

- **Learning Focus**: All business logic stays in PHP (the goal of this project)
- **Single Source of Truth**: Change a formula once in YAML, it updates everywhere
- **Testable**: Unit test the config loading and formula calculations in PHPUnit
- **Flexible**: Easy to add seasonal events, A/B testing, or difficulty modes

#### Directory Structure

```
config/
└── game/
    ├── buildings.yaml     # {id, name, baseCost, baseRevenue, icon}
    ├── upgrades.yaml      # {id, targetBuilding, multiplier, unlockCondition}
    └── formulas.yaml      # {costGrowthRate: 1.15, revenuePerSecond: ...}

src/Domain/
└── Config/
    ├── GameConfigLoader.php           # Parses YAML → DTO
    ├── DTO/
    │   ├── GameConfigDTO.php          # Root config object
    │   ├── BuildingConfigDTO.php      # Single building definition
    │   ├── UpgradeConfigDTO.php       # Single upgrade definition
    │   └── FormulasConfigDTO.php      # Math formulas & coefficients
    └── Validator/
        └── GameConfigValidator.php    # Validates config at cache warmup
```

#### Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│  1. YAML Config (config/game/*.yaml)                        │
│     └── Parsed by GameConfigLoader                          │
│         └── Validated by GameConfigValidator                │
│             └── Cached by Symfony                           │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  2. Controller                                              │
│     └── Injects GameConfigDTO                               │
│         └── Serializes to JSON                              │
│             └── Passes to Twig                              │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  3. Twig Template                                           │
│     {{ svelte_component('Game', { config: configJson }) }}  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  4. Svelte Component                                        │
│     let { config } = $props();                              │
│     // Uses config.formulas.costGrowthRate, etc.            │
│     // NO hardcoded numbers!                                │
└─────────────────────────────────────────────────────────────┘
```

#### Example: Generic Formula in Svelte

```javascript
// ❌ FORBIDDEN - Hardcoded values
const cost = baseCost * Math.pow(1.15, owned);

// ✅ CORRECT - Config-driven
const cost = Math.floor(
    building.baseCost * Math.pow(config.formulas.costGrowthRate, owned)
);
```

#### Validation Rules

- Config is validated at **cache warmup** (not runtime)
- PHPStan ensures DTOs are strictly typed
- Missing or invalid config = **application fails to boot** (fail fast)

---

## Backend (PHP 8.4, Symfony 7.4, PostgreSQL)

### Architecture & Layering Rules

We follow a strict **Domain-Driven Design** approach. Separation of concerns is mandatory.

#### Directory Structure

Strictly adhere to the defined namespaces:

```
src/
├── Domain/           # Pure business logic
├── Application/      # Use cases & orchestration
├── Infrastructure/   # Technical implementations
└── UserInterface/    # Controllers & API endpoints
```

#### Layer Responsibilities

##### `/Domain` Layer (The Heart)

- Contains **Entities** (`Company`, `Department`), **Value Objects**, and **Interfaces**
- **Forbidden**: Importing from `/Infrastructure` or `/UserInterface`
- **Focus**: Pure PHP logic (e.g., `OfflineCalculatorService`)

##### `/Application` Layer (The Orchestrator)

- Contains **Commands**, **Handlers**, and **DTOs**
- Handles the "Game Loop" logic and dispatches Domain Events

##### `/Infrastructure` Layer (Technical Implementation)

- Concrete **Repositories** (Doctrine)
- **Mercure Integration**: Implementations of Domain interfaces that push updates to the Mercure Hub

### Data Access & CQRS Pattern

We use a variation of **CQRS** adapted for the Game State.

#### Write Operations (Game Loop)

- All state changes pass through a **Command** (e.g., `SyncGameCommand`)
- The **Handler** processes a batch of actions atomically

#### Read Operations

- Use **DTOs** to serialize the full Game State efficiently for the initial load

#### Repositories

- **Interfaces** in `/Domain`
- **Implementation** in `/Infrastructure`
- ⚠️ **NEVER** use `EntityManager` directly in Controllers

### Real-Time & Async (Mercure & Messenger)

#### Mercure (Server-Sent Events)

Used for pushing updates from the server:
- "A partner contract has expired"
- "Leaderboard updated"

> **Rule**: Controllers/Services DO NOT publish directly to the Hub. They dispatch a **Domain Event** (e.g., `PartnerExpiredEvent`), which is handled by an Infrastructure Subscriber that talks to Mercure.

#### Symfony Messenger

Used to handle background tasks (e.g., complex ROI recalculations).

### PHP Coding Style & Rules

| Rule | Description |
|------|-------------|
| **PHPStan Level 8** | Code must pass static analysis |
| **Strict Typing** | No loose typing. Use `int` for money (stored in cents) or `bcmath` |
| **Readonly Classes** | Use `readonly class` for all DTOs and immutable Value Objects |
| **PHP 8 Attributes** | Use Attributes for `Route`, `ORM`, and `Validation` mapping |
| **Thin Controllers** | Controllers are strictly for HTTP glue: deserialize request → dispatch Command → return JSON response |

---

## Frontend (Svelte 5, Twig, TailwindCSS 4)

The frontend acts as a "Remote Control" for the game state, leveraging the latest reactive patterns.

### JavaScript & Svelte 5

#### Svelte 5 Syntax (Runes)

You **MUST** use the new Runes syntax:

```javascript
// ✅ Correct - Svelte 5 Runes
$state, $derived, $effect, $props
```

```javascript
// ❌ Forbidden - Svelte 4 Legacy
export let, $:, createEventDispatcher
```

#### State Management (`.svelte.js`)

- **DO NOT** use `svelte/store` (`writable`, `readable`)
- Use **Global Reactive Objects** via `.svelte.js` files containing classes with `$state` fields
- The Game State object handles the Optimistic Update logic internally

#### Mercure Integration

The frontend listens to the global Mercure `EventSource` and updates the reactive state (`$state`) accordingly.

### Twig & Integration

#### Hybrid Approach

1. **Twig** renders the layout and passes the `INITIAL_STATE` (JSON) to the mounting point
2. **Svelte** takes over for the interactive game loop using `symfony/ux-svelte`

### Styling (TailwindCSS v4)

| Aspect | Configuration |
|--------|---------------|
| **Build System** | Vite (via Symfony AssetMapper or Encore, Vite recommended for Svelte 5) |
| **Engine** | New Oxide engine (v4) |
| **Configuration** | CSS-first configuration (no `tailwind.config.js` needed for basic setups) |

---

## Testing

Quality is key to ensure the math is correct (no infinite money glitches).

### Test Types

#### Unit Tests (`tests/Unit/`) - **Critical Priority**

- Test the **Domain Logic**: `OfflineCalculatorTest`, `RevenueCalculatorTest`
- Mock everything
- Tests must run in **milliseconds**

#### Integration Tests (`tests/Integration/`)

- Test the **Command Handlers**: Ensure `SyncGameHandler` correctly updates the database
- Test the **Mercure Dispatch**: Ensure saving a game state triggers the correct Event dispatch (mocking the actual Hub)

#### E2E Tests (`tests/E2E/`)

Using **Playwright**:

- Test the "Critical Path": Login → Click button → Buy Upgrade → Check if production increased
- Validate Svelte 5 reactivity (DOM updates) on user interaction

### Commands

```bash
make tests                # Run PHPUnit
make fix                  # Run CS-Fixer & PHPStan
npm run build             # Build Svelte/Tailwind
```

---

## Tech Stack Summary

| Layer | Technology |
|-------|------------|
| Backend Framework | Symfony 7.4 |
| PHP Version | 8.4 |
| Database | PostgreSQL |
| Frontend Framework | Svelte 5 (Runes) |
| CSS Framework | TailwindCSS 4 (Oxide) |
| Templating | Twig |
| Real-Time | Mercure (SSE) |
| Async Processing | Symfony Messenger |
| Build Tool | Vite |
| Testing | PHPUnit, Playwright |

---

## AI Assistant Instructions (Context7 MCP)

### Using Context7 for Recent Libraries

This project uses **recent library versions** that may not be in your training data. **Always use the Context7 MCP server** to fetch up-to-date documentation before writing code for:

| Library | Reason | Context7 Query |
|---------|--------|----------------|
| **Svelte 5** | Runes syntax (`$state`, `$derived`, `$props`) is NEW | `svelte` - topic: "runes" |
| **TailwindCSS 4** | Oxide engine, CSS-first config, new syntax | `tailwindcss` - topic: "v4" |
| **Symfony UX Svelte** | Integration patterns may have changed | `symfony/ux-svelte` |

### Mandatory Workflow

```
1. Before writing Svelte code:
   → Call context7 resolve-library-id for "svelte"
   → Call context7 get-library-docs with topic "runes" or "state"

2. Before writing TailwindCSS:
   → Call context7 resolve-library-id for "tailwindcss"
   → Call context7 get-library-docs with topic "v4" or "configuration"

3. When unsure about API:
   → ALWAYS fetch docs first, don't guess from outdated knowledge
```

### Why This Matters

- **Svelte 5 Runes** completely changed reactivity (no more `$:`, `export let`)
- **TailwindCSS 4** uses CSS-native config (no more `tailwind.config.js` for most cases)
- Using outdated patterns will break the codebase or create inconsistencies

> ⚠️ **Rule**: When in doubt, fetch Context7 docs. It's better to spend 5 seconds fetching than 5 minutes debugging outdated syntax.
