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
