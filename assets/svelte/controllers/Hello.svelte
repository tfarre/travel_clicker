<script>
    // Svelte 5 Runes syntax - using $props() instead of export let
    let { name = 'World' } = $props();

    // Reactive state with $state rune
    let count = $state(0);

    // Derived state with $derived rune
    let doubled = $derived(count * 2);

    function increment() {
        count++;
    }
</script>

<div class="p-6 bg-white rounded-lg shadow-md max-w-md mx-auto mt-8">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">
        Hello, {name}! 👋
    </h1>

    <div class="space-y-4">
        <p class="text-gray-600">
            This is a Svelte 5 component using the new Runes syntax.
        </p>

        <div class="flex items-center gap-4">
            <button
                onclick={increment}
                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors"
            >
                Count: {count}
            </button>
            <span class="text-gray-500">
                Doubled: {doubled}
            </span>
        </div>
    </div>
</div>
