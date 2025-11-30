<script>
    import { gameState } from '../lib/GameState.svelte.js';

    let { building } = $props();

    // Derived values for this building
    let owned = $derived(gameState.buildings[building.id]?.owned ?? 0);
    let cost = $derived(gameState.getBuildingCost(building.id));
    let canAfford = $derived(gameState.canAffordBuilding(building.id));

    function handleBuy() {
        gameState.buyBuilding(building.id);
    }

    // Format the building's effect description
    function getEffectText() {
        if (building.production !== undefined) {
            return `+${building.production}/sec`;
        }
        return '';
    }
</script>

<div
    class="border rounded-lg p-3 transition-all duration-200"
    class:border-gray-200={canAfford}
    class:bg-gray-50={!canAfford}
    class:border-gray-100={!canAfford}
    class:opacity-75={!canAfford}
>
    <div class="flex items-start justify-between gap-3">
        <!-- Building Info -->
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <span class="text-xl">{building.icon}</span>
                <span class="font-medium text-gray-800 truncate">
                    {building.name}
                </span>
                {#if owned > 0}
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                        ×{owned}
                    </span>
                {/if}
            </div>

            <p class="text-xs text-gray-500 mt-1 truncate">
                {building.description}
            </p>

            <div class="text-xs text-green-600 font-medium mt-1">
                {getEffectText()}
            </div>
        </div>

        <!-- Buy Button -->
        <button
            onclick={handleBuy}
            disabled={!canAfford}
            class="flex-shrink-0 px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                   focus:outline-none focus:ring-2 focus:ring-offset-1"
            class:bg-green-500={canAfford}
            class:hover:bg-green-600={canAfford}
            class:text-white={canAfford}
            class:focus:ring-green-400={canAfford}
            class:bg-gray-200={!canAfford}
            class:text-gray-400={!canAfford}
            class:cursor-not-allowed={!canAfford}
        >
            {gameState.formatMoney(cost)}
        </button>
    </div>
</div>
