# Performance Guide

A comprehensive guide to performance optimizations in this Craft CMS 5 boilerplate.

## Quick Start

### Development

```bash
npm run dev    # Start Vite dev server with HMR
```

### Production Build

```bash
npm run build  # Build optimized bundles
```

### Clear Caches

```bash
php craft clear-caches/all     # Clear all Craft caches
php craft blitz/cache/clear    # Clear Blitz cache
php craft blitz/cache/warm     # Warm Blitz cache
```

## Performance Features

### Frontend Optimizations

#### Code Splitting

JavaScript is split into separate chunks loaded on-demand:

| Chunk | When Loaded |
|-------|-------------|
| `app.js` | Always (entry point) |
| `alpine.js` | When Alpine components detected |
| `swiper.js` | When carousel elements present |
| `lucide.js` | When icons needed |
| `vanilla-tilt.js` | When tilt effects present |

#### Lazy Loading

Images use native lazy loading:
```html
<img loading="lazy" src="..." />
```

JavaScript modules load conditionally based on page content.

#### Image Optimization

- **ImageOptimize plugin** handles responsive images
- WebP format served with JPEG/PNG fallback
- Placeholder images (LQIP) for blur-up effect
- Focal point support for cropped images

### Backend Optimizations

#### Eager Loading

Content blocks use eager loading to prevent N+1 queries:

```twig
{% set blocks = entry.contentBlocks
    .with(['singleImage', 'singleVideo', 'images'])
    .all() %}
```

Matrix fields use the `.eagerly()` pattern:

```twig
{% for block in entry.contentBlocks.eagerly() %}
    {{ block.singleImage.one().url }}
{% endfor %}
```

#### Template Caching

Navigation is cached for fast page loads:

```twig
{% cache globally for 1 day tagged "navigation" %}
    {# Navigation markup #}
{% endcache %}
```

#### Full-Page Caching

Blitz plugin provides static page caching:

- Enabled in `staging` and `production` environments
- Automatic invalidation when content changes
- Cache warming via CLI

### Build Optimizations

#### Vite Configuration

- **esbuild minification** - Faster than Terser
- **Manual chunks** - Optimal caching for vendor libraries
- **Legacy plugin** - IE11 support (can be removed if not needed)
- **Compression** - Brotli and Gzip pre-compression

#### CSS Optimization

- **Tailwind JIT** - Only used utilities included
- **cssnano** - Additional minification in production
- **Font subsetting** - Only used weights loaded

## Configuration Files

| File | Purpose |
|------|---------|
| `vite.config.js` | Build configuration |
| `tailwind.config.js` | Tailwind settings |
| `postcss.config.cjs` | CSS processing |
| `config/blitz.php` | Full-page caching |
| `config/image-optimize.php` | Image optimization |

## Best Practices

### Templates

1. **Use eager loading** for related content:
   ```twig
   {# Good #}
   {% set entries = craft.entries().with(['image']).all() %}
   
   {# Bad - causes N+1 queries #}
   {% set entries = craft.entries().all() %}
   ```

2. **Isolate includes** with the `only` keyword:
   ```twig
   {% include '_block.twig' with {'data': data} only %}
   ```

3. **Cache expensive operations**:
   ```twig
   {% cache globally for 1 hour %}
       {# Expensive queries/rendering #}
   {% endcache %}
   ```

### JavaScript

1. **Lazy load heavy libraries**:
   ```typescript
   // Import dynamically when needed
   const Swiper = await import('swiper');
   ```

2. **Tree-shake imports**:
   ```typescript
   // Good - specific import
   import { createIcons, ChevronRight } from 'lucide';
   
   // Bad - imports everything
   import * as lucide from 'lucide';
   ```

### CSS

1. **Use Tailwind utilities** instead of custom CSS
2. **Avoid deep nesting** in custom styles
3. **Purge unused styles** - handled automatically by JIT

### Images

1. **Use ImageOptimize transforms** for responsive images
2. **Set explicit dimensions** to prevent layout shift
3. **Use appropriate formats** - WebP for photos, SVG for icons

## Monitoring

### Recommended Tools

- **Lighthouse** - Core Web Vitals testing
- **WebPageTest** - Detailed waterfall analysis
- **Craft Debug Bar** - Database query monitoring
- **Blitz Dashboard** - Cache hit rate

### Key Metrics

| Metric | Target |
|--------|--------|
| First Contentful Paint | < 1.5s |
| Largest Contentful Paint | < 2.5s |
| Time to Interactive | < 3.0s |
| Cumulative Layout Shift | < 0.1 |

## Troubleshooting

### Slow Page Loads

1. Check database queries (enable Debug Bar)
2. Verify Blitz cache is active
3. Review network waterfall for blocking resources

### Large Bundle Sizes

1. Run `npm run build` and check output
2. Verify unused code is tree-shaken
3. Consider removing `@vitejs/plugin-legacy` if IE11 not needed

### Cache Issues

```bash
# Clear all caches
php craft clear-caches/all
npm run build
php craft blitz/cache/refresh
```

## Documentation

Detailed audit documents are in `.ralph/`:

- `performance-metrics.md` - Before/after measurements
- `bundle-analysis.md` - JavaScript analysis
- `image-optimization-audit.md` - Image handling
- `twig-template-analysis.md` - Query patterns
- `blitz-caching-audit.md` - Caching strategy
- `css-optimization-audit.md` - CSS optimization
- `font-optimization-audit.md` - Font loading
- `database-query-audit.md` - Query patterns
- `template-performance-audit.md` - Template structure
- `build-performance-audit.md` - Build configuration
