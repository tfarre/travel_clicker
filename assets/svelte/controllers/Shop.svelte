<script lang="ts">
    import { gameState } from '../lib/GameState.svelte';
    import BuildingCard from './BuildingCard.svelte';
    import VerticalCard from './VerticalCard.svelte';
</script>

<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">
        🛒 Boutique
    </h2>

    <!-- Marketing Section -->
    <div class="mb-6">
        <h3 class="text-md font-medium text-blue-600 mb-3 flex items-center gap-2">
            <span>📢</span> Marketing
            <span class="text-xs font-normal text-gray-500">(visiteurs passifs)</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            {#each gameState.config?.marketing ?? [] as building}
                <BuildingCard {building} />
            {/each}
        </div>
    </div>

    <!-- Verticals Section -->
    <div>
        <h3 class="text-md font-medium text-purple-600 mb-3 flex items-center gap-2">
            <span>🌍</span> Verticales Voyage
            <span class="text-xs font-normal text-gray-500">(répartition du marché)</span>
        </h3>

        <p class="text-xs text-gray-500 mb-3">
            {gameState.unlockedVerticalsCount} verticale(s) active(s) —
            Chaque vente est répartie selon l'attractivité de vos offres.
        </p>

        <div class="grid grid-cols-1 gap-3">
            {#each gameState.config?.verticals ?? [] as vertical}
                <VerticalCard {vertical} />
            {/each}
        </div>
    </div>
</div>
