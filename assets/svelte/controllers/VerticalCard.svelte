<script lang="ts">
    import { gameState } from '../lib/GameState.svelte';
    import type { VerticalConfig } from '../types';

    let { vertical }: { vertical: VerticalConfig } = $props();

    // Derived values from state
    let level = $derived(gameState.verticals[vertical.id]?.level ?? 0);
    let isUnlocked = $derived(level > 0);
    let upgradeCost = $derived(gameState.getVerticalUpgradeCost(vertical.id));
    let currentPrice = $derived(gameState.getVerticalCurrentPrice(vertical.id));
    let canAfford = $derived(gameState.canAffordVertical(vertical.id));

    // Market share (only for unlocked verticals)
    // FIX: $derived should return a VALUE, not a function
    let marketShare = $derived(
        !isUnlocked || gameState.totalAttractivity === 0
            ? 0
            : (vertical.attractivity / gameState.totalAttractivity) * 100
    );

    function handleUpgrade(): void {
        gameState.upgradeVertical(vertical.id);
    }
</script>

<div
    class="border rounded-lg p-3 transition-all duration-200 {isUnlocked
        ? 'bg-white border-green-200 hover:border-green-300'
        : 'bg-gray-50 border-gray-200 hover:border-gray-300'}"
>
    <div class="flex items-start justify-between gap-3">
        <!-- Icon & Info -->
        <div class="flex items-start gap-3 flex-1">
            <span class="text-2xl" role="img" aria-label={vertical.name}>
                {vertical.icon}
            </span>

            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <h4 class="font-medium text-gray-800 truncate">
                        {vertical.name}
                    </h4>
                    {#if isUnlocked}
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                            Niv. {level}
                        </span>
                    {:else}
                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">
                            🔒 Verrouillé
                        </span>
                    {/if}
                </div>

                <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">
                    {vertical.description}
                </p>

                {#if isUnlocked}
                    <!-- Stats for unlocked vertical -->
                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                        <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded">
                            💰 {gameState.formatMoney(currentPrice)}/vente
                        </span>
                        <span class="bg-purple-50 text-purple-700 px-2 py-1 rounded">
                            📊 {marketShare.toFixed(1)}% du marché
                        </span>
                    </div>
                {:else}
                    <!-- Preview for locked vertical -->
                    <div class="mt-2 text-xs text-gray-400">
                        💰 Prix de base: {gameState.formatMoney(vertical.basePrice)}
                    </div>
                {/if}
            </div>
        </div>

        <!-- Unlock/Upgrade Button -->
        <div class="flex flex-col items-end gap-1">
            <button
                onclick={handleUpgrade}
                disabled={!canAfford}
                class="px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200 whitespace-nowrap
                    {canAfford
                        ? isUnlocked
                            ? 'bg-green-500 hover:bg-green-600 text-white shadow-sm hover:shadow'
                            : 'bg-blue-500 hover:bg-blue-600 text-white shadow-sm hover:shadow'
                        : 'bg-gray-200 text-gray-400 cursor-not-allowed'}"
            >
                {#if isUnlocked}
                    ⬆️ Améliorer
                {:else}
                    🔓 Débloquer
                {/if}
            </button>

            <span class="text-xs {canAfford ? 'text-green-600' : 'text-gray-500'}">
                {gameState.formatMoney(upgradeCost)}
            </span>
        </div>
    </div>
</div>
