/**
 * Lazy loading utilities for code splitting
 * Loads modules on-demand to reduce initial bundle size
 */

// Cache for loaded modules
const loadedModules = new Set<string>();

/**
 * Lazy load Swiper and make it available on window.Swiper
 * Returns a promise that resolves when Swiper is ready
 */
export async function loadSwiper(): Promise<void> {
    if (loadedModules.has('swiper')) {
        return Promise.resolve();
    }

    if (typeof window !== 'undefined' && (window as any).Swiper) {
        loadedModules.add('swiper');
        return Promise.resolve();
    }

    try {
        // Import Swiper bundle
        const { default: Swiper } = await import('swiper/bundle');
        
        // Import Swiper CSS dynamically (side-effect import)
        // @ts-ignore - CSS imports don't have type definitions
        await import('swiper/css/bundle');
        
        (window as any).Swiper = Swiper;
        loadedModules.add('swiper');
    } catch (error) {
        console.error('Failed to load Swiper:', error);
        throw error;
    }
}

/**
 * Lazy load Alpine.js plugins
 * Returns a promise that resolves with the loaded plugins
 */
export async function loadAlpinePlugins(pluginNames: string[]): Promise<any[]> {
    const plugins: any[] = [];
    const loadPromises: Promise<any>[] = [];

    for (const pluginName of pluginNames) {
        if (loadedModules.has(`alpine-${pluginName}`)) {
            continue;
        }

        let importPromise: Promise<any>;
        switch (pluginName) {
            case 'anchor':
                importPromise = import('@alpinejs/anchor').then(m => m.default);
                break;
            case 'collapse':
                importPromise = import('@alpinejs/collapse').then(m => m.default);
                break;
            case 'focus':
                importPromise = import('@alpinejs/focus').then(m => m.default);
                break;
            case 'intersect':
                importPromise = import('@alpinejs/intersect').then(m => m.default);
                break;
            case 'mask':
                importPromise = import('@alpinejs/mask').then(m => m.default);
                break;
            case 'persist':
                importPromise = import('@alpinejs/persist').then(m => m.default);
                break;
            case 'sort':
                // @ts-ignore - no type definitions available
                importPromise = import('@alpinejs/sort').then(m => m.default);
                break;
            case 'resize':
                // @ts-ignore - no type definitions available
                importPromise = import('@alpinejs/resize').then(m => m.default);
                break;
            default:
                console.warn(`Unknown Alpine plugin: ${pluginName}`);
                continue;
        }

        loadPromises.push(
            importPromise.then(plugin => {
                loadedModules.add(`alpine-${pluginName}`);
                return plugin;
            })
        );
    }

    const loadedPlugins = await Promise.all(loadPromises);
    plugins.push(...loadedPlugins);
    return plugins;
}

/**
 * Lazy load Vanilla Tilt
 */
export async function loadVanillaTilt(): Promise<void> {
    if (loadedModules.has('vanilla-tilt')) {
        return Promise.resolve();
    }

    if (typeof window !== 'undefined' && (window as any).tilt) {
        loadedModules.add('vanilla-tilt');
        return Promise.resolve();
    }

    try {
        const { default: VanillaTilt } = await import('vanilla-tilt');
        (window as any).tilt = VanillaTilt;
        loadedModules.add('vanilla-tilt');
    } catch (error) {
        console.error('Failed to load Vanilla Tilt:', error);
        throw error;
    }
}

/**
 * Detect if Swiper is needed on the page
 */
export function needsSwiper(): boolean {
    if (typeof document === 'undefined') return false;
    
    return !!(
        document.querySelector('.swiper') ||
        document.querySelector('[x-data*="swiper"]') ||
        document.querySelector('[x-data*="Swiper"]')
    );
}

/**
 * Detect which Alpine plugins are needed based on template usage
 */
export function detectAlpinePlugins(): string[] {
    if (typeof document === 'undefined') return [];
    
    const plugins: string[] = [];
    
    // Check for plugin usage in templates
    if (document.querySelector('[x-collapse]')) plugins.push('collapse');
    if (document.querySelector('[x-intersect]')) plugins.push('intersect');
    if (document.querySelector('[x-mask]')) plugins.push('mask');
    if (document.querySelector('[x-persist]')) plugins.push('persist');
    if (document.querySelector('[x-anchor]')) plugins.push('anchor');
    if (document.querySelector('[x-sort]')) plugins.push('sort');
    if (document.querySelector('[x-resize]')) plugins.push('resize');
    
    // Focus plugin is commonly used, check for focus-related attributes
    if (document.querySelector('[x-focus]') || document.querySelector('[x-trap]')) {
        plugins.push('focus');
    }
    
    return [...new Set(plugins)]; // Remove duplicates
}

/**
 * Detect if Vanilla Tilt is needed
 */
export function needsVanillaTilt(): boolean {
    if (typeof document === 'undefined') return false;
    
    return !!(
        document.querySelector('[data-tilt]') ||
        document.querySelector('.tilt')
    );
}
