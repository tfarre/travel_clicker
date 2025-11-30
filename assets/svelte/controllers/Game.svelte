<script lang="ts">
    import { gameState } from '../lib/GameState.svelte';
    import type { GameConfig } from '../types/game';
    import ClickButton from './ClickButton.svelte';
    import Dashboard from './Dashboard.svelte';
    import Shop from './Shop.svelte';

    // Receive config from Twig template (Config-Driven Design)
    let { config }: { config: GameConfig } = $props();

    // Initialize game state with config on mount
    $effect(() => {
        if (config && !gameState.isInitialized) {
            gameState.init(config);
        }
    });

    // Cleanup on unmount
    $effect(() => {
        return () => {
            gameState.destroy();
        };
    });
</script>

<div class="container mx-auto px-4 py-6 max-w-4xl">
    <!-- Error Toast -->
    {#if gameState.errorMessage}
        <div class="fixed top-4 right-4 z-50 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg animate-pulse">
            ⚠️ {gameState.errorMessage}
        </div>
    {/if}

    <!-- Sync Indicator -->
    {#if gameState.isSyncing}
        <div class="fixed top-4 left-4 z-50 bg-blue-500 text-white px-4 py-2 rounded-lg shadow-lg text-sm">
            🔄 Synchronisation...
        </div>
    {/if}

    <!-- Header -->
    <header class="text-center mb-8">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">
            🌍 Travel Clicker
        </h1>
        <p class="text-gray-600">
            Développez votre marketplace de voyages !
        </p>
    </header>

    {#if gameState.isInitialized}
        <!-- Money Display -->
        <div class="text-center mb-6">
            <div class="inline-block bg-white rounded-xl shadow-lg px-8 py-4">
                <span class="text-3xl md:text-4xl font-bold text-green-600">
                    💰 {gameState.moneyFormatted}
                </span>
            </div>
        </div>

        <!-- Main Game Area -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Click Area + Stats -->
            <div class="lg:col-span-1 space-y-4">
                <ClickButton />
                <Dashboard />
            </div>

            <!-- Right: Shop -->
            <div class="lg:col-span-2">
                <Shop />
            </div>
        </div>
    {:else}
        <!-- Loading State -->
        <div class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
            <span class="ml-4 text-gray-600">Chargement...</span>
        </div>
    {/if}
</div>
