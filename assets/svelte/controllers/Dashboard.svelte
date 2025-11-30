<script>
    import { gameState } from '../lib/GameState.svelte.js';

    // Format large numbers nicely
    function formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'k';
        }
        return Math.floor(num).toLocaleString('fr-FR');
    }
</script>

<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">
        📊 Statistiques
    </h2>

    <div class="space-y-4">
        <!-- Total Visitors -->
        <div class="flex justify-between items-center">
            <span class="text-gray-600">👥 Visiteurs totaux</span>
            <span class="font-semibold text-gray-800">
                {formatNumber(gameState.totalVisitors)}
            </span>
        </div>

        <!-- Visitors per Second -->
        <div class="flex justify-between items-center">
            <span class="text-gray-600">📈 Visiteurs/sec</span>
            <span class="font-semibold text-blue-600">
                {gameState.visitorsPerSecond.toFixed(1)}/s
            </span>
        </div>

        <!-- Total Sales -->
        <div class="flex justify-between items-center">
            <span class="text-gray-600">🛒 Ventes totales</span>
            <span class="font-semibold text-green-600">
                {formatNumber(gameState.totalSales)}
            </span>
        </div>

        <!-- Expected Revenue per Batch -->
        <div class="flex justify-between items-center">
            <span class="text-gray-600">💵 Commission/lot</span>
            <span class="font-semibold text-green-600">
                {gameState.formatMoney(gameState.expectedRevenuePerBatch)}
            </span>
        </div>

        <!-- Active Verticals -->
        <div class="flex justify-between items-center">
            <span class="text-gray-600">🌍 Verticales actives</span>
            <span class="font-semibold text-purple-600">
                {gameState.unlockedVerticalsCount}/{gameState.config?.verticals?.length ?? 0}
            </span>
        </div>

        <!-- Sale Progress Bar -->
        <div class="pt-2">
            <div class="flex justify-between text-sm text-gray-500 mb-1">
                <span>Prochain lot de ventes</span>
                <span>
                    {Math.floor(gameState.visitorsTowardsSale)}/{gameState.config?.formulas.saleTriggerThreshold ?? 100}
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                <div
                    class="bg-gradient-to-r from-green-400 to-green-600 h-3 rounded-full transition-all duration-200"
                    style="width: {Math.min(gameState.saleProgress, 100)}%"
                ></div>
            </div>
        </div>

        <!-- Market Distribution (if any verticals unlocked) -->
        {#if gameState.marketDistribution.length > 0}
            <div class="pt-4 border-t border-gray-100">
                <h3 class="text-sm font-medium text-gray-600 mb-2">
                    📊 Répartition du marché
                </h3>
                <div class="space-y-2">
                    {#each gameState.marketDistribution as item}
                        <div class="flex items-center gap-2">
                            <span class="text-sm">{item.icon}</span>
                            <div class="flex-1">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-600">{item.name}</span>
                                    <span class="text-gray-500">{item.marketShare.toFixed(1)}%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                    <div
                                        class="bg-purple-400 h-1.5 rounded-full transition-all duration-300"
                                        style="width: {item.marketShare}%"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    {/each}
                </div>
            </div>
        {/if}
    </div>
</div>
