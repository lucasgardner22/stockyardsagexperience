console.log('VITE_NODE_CONFIG_DIR:', import.meta.env.VITE_NODE_CONFIG_DIR);

import 'vite/modulepreload-polyfill';
import './style.css'

// Lazy loading utilities
import {
    loadSwiper,
    loadAlpinePlugins,
    loadVanillaTilt,
    needsSwiper,
    detectAlpinePlugins,
    needsVanillaTilt
} from './utils/lazy-load';

// TailwindCSS Intersection Init (always needed)
// Docs: https://github.com/heidkaemper/tailwindcss-intersect
import { Observer } from 'tailwindcss-intersect';
Observer.start();

// Lucide init ----------------------------------------------
// Docs: https://lucide.dev/guide/packages/lucide
// Using tree-shakeable individual imports - only importing icons actually used in templates
// Icons used: chevron-right, arrow-right, info, octagon-x, message-circle-question, triangle-alert, circle-check-big
import {
    createIcons,
    ChevronRight,
    ArrowRight,
    Info,
    CircleCheckBig,
    MessageCircleQuestion,
    TriangleAlert,
    OctagonX
} from "lucide";

createIcons({
    attrs: {
        'stroke-width': 2,
        stroke: '#333',
    },
    icons: {
        ChevronRight,
        ArrowRight,
        Info,
        CircleCheckBig,
        MessageCircleQuestion,
        TriangleAlert,
        OctagonX
    }
})

// Alpinejs init ---------------------------------------------
// Core Alpine is always loaded, plugins are loaded conditionally
// Docs: https://alpinejs.dev/start-here
import Alpine from 'alpinejs'

// Initialize Alpine with lazy-loaded plugins
async function initAlpine() {
    // Wait for DOM to be ready (or proceed if already ready)
    if (document.readyState === 'loading') {
        await new Promise(resolve => {
            document.addEventListener('DOMContentLoaded', resolve);
        });
    }

    // Detect what's needed on the page
    const neededPlugins = detectAlpinePlugins();
    const needsSwiperModule = needsSwiper();
    const needsTilt = needsVanillaTilt();

    // Load required modules before Alpine starts
    const loadPromises: Promise<any>[] = [];

    if (needsSwiperModule) {
        loadPromises.push(loadSwiper());
    }

    if (needsTilt) {
        loadPromises.push(loadVanillaTilt());
    }

    // Load Alpine plugins if needed
    if (neededPlugins.length > 0) {
        loadPromises.push(
            loadAlpinePlugins(neededPlugins).then(plugins => {
                Alpine.plugin(plugins);
            })
        );
    }

    // Wait for all modules to load (or proceed immediately if nothing needed)
    if (loadPromises.length > 0) {
        await Promise.all(loadPromises);
    }

    // Make Alpine available globally
    // @ts-ignore
    window.Alpine = Alpine;
    
    // Start Alpine
    Alpine.start();
}

// Start initialization
initAlpine().catch(error => {
    console.error('Failed to initialize Alpine:', error);
    // Fallback: start Alpine without plugins if loading fails
    // @ts-ignore
    window.Alpine = Alpine;
    Alpine.start();
});

// -----------------------------------------------------------
if (import.meta.hot) {
    import.meta.hot.accept(() => {
        console.log("HMR")
    });
}