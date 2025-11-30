<script lang="ts">
    import { gameState } from '../lib/GameState.svelte';

    // Track click animation state
    let isClicking = $state(false);
    let clickCount = $state(0);

    function handleClick(): void {
        gameState.click();

        // Trigger click animation
        isClicking = true;
        clickCount += 1;

        setTimeout(() => {
            isClicking = false;
        }, 100);
    }
</script>

<div class="bg-white rounded-xl shadow-lg p-6 text-center relative">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">
        Attirer des visiteurs
    </h2>

    <button
        onclick={handleClick}
        class="relative w-32 h-32 md:w-40 md:h-40 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600
               text-white text-5xl md:text-6xl shadow-lg
               hover:from-blue-600 hover:to-indigo-700 hover:shadow-xl
               active:scale-95 transition-all duration-100
               focus:outline-none focus:ring-4 focus:ring-blue-300"
        class:scale-95={isClicking}
    >
        🖱️
    </button>

    <p class="mt-4 text-sm text-gray-500">
        +{gameState.config?.formulas.visitorsPerClick ?? 1} visiteur par clic
    </p>

    <!-- Click feedback particles (optional visual) -->
    {#if isClicking}
        <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
            <span class="text-2xl animate-ping text-green-500">+1</span>
        </div>
    {/if}
</div>
