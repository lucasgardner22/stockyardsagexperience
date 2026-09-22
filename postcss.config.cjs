module.exports = {
    plugins: {
        // @tailwindcss/postcss includes autoprefixer
        '@tailwindcss/postcss': {},
        // Using cssnano for production optimization
        // https://tailwindcss.com/docs/optimizing-for-production
        ...(process.env.NODE_ENV === 'production' ? {
            'cssnano': {
                preset: ['default', {
                    // Merge duplicate rules
                    mergeLonghand: true,
                    mergeRules: true,
                    // Remove comments
                    discardComments: { removeAll: true },
                    // Normalize CSS values
                    normalizeWhitespace: true,
                    // Reduce calc expressions
                    calc: true,
                    // Optimize color values
                    colormin: true,
                    // Convert values to more efficient representations
                    convertValues: true,
                    // Remove duplicate declarations
                    discardDuplicates: true,
                    // Remove empty rules
                    discardEmpty: true,
                    // Minify selectors
                    minifySelectors: true,
                    // Reduce gradient values
                    minifyGradients: true,
                    // Unique selectors only
                    uniqueSelectors: true,
                }]
            }
        } : {}),
    }
}