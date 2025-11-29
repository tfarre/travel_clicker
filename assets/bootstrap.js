import { startStimulusApp, registerControllers } from 'vite-plugin-symfony/stimulus/helpers';

// Register Svelte components for use with Stimulus
import { registerSvelteControllerComponents } from 'vite-plugin-symfony/stimulus/helpers/svelte';

// Register all Svelte components from the svelte/controllers directory
registerSvelteControllerComponents(
    import.meta.glob('./svelte/controllers/**/*.svelte', { eager: true })
);

// Start the Stimulus application
const app = startStimulusApp();

// Register custom Stimulus controllers from the controllers directory
registerControllers(
    app,
    import.meta.glob('./controllers/*_controller.js', { eager: true })
);
