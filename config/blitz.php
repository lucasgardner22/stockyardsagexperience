<?php
/**
 * Blitz Plugin Configuration
 *
 * Full-page caching with intelligent invalidation for Craft CMS.
 *
 * @see https://putyourlightson.com/plugins/blitz
 */

use craft\helpers\App;

return [
    '*' => [
        // Enable/disable caching
        'cachingEnabled' => false,

        // Include URLs matching these patterns in caching
        'includedUriPatterns' => [
            [
                'siteId' => '',
                'uriPattern' => '.*',
            ],
        ],

        // Exclude URLs matching these patterns from caching
        'excludedUriPatterns' => [
            // Exclude admin and action URLs
            [
                'siteId' => '',
                'uriPattern' => 'admin.*',
            ],
            [
                'siteId' => '',
                'uriPattern' => 'actions/.*',
            ],
            // Exclude user account pages
            [
                'siteId' => '',
                'uriPattern' => 'account.*',
            ],
            // Exclude preview URLs
            [
                'siteId' => '',
                'uriPattern' => '.*\?token=.*',
            ],
        ],

        // Cache storage driver (file, yii, or custom)
        'cacheStorageType' => 'putyourlightson\blitz\drivers\storage\FileStorage',

        // File storage settings
        'cacheStorageSettings' => [
            'folderPath' => '@webroot/cache/blitz',
            'compressCachedValues' => true,
            'countCachedFiles' => true,
        ],

        // How to refresh cache (queue jobs, immediately, or generate URI only)
        'refreshMode' => 'putyourlightson\blitz\drivers\refreshers\LocalRefresher::REFRESH_MODE_CLEAR_GENERATE_QUEUE',

        // URI patterns to generate on cache warm
        'warmCacheDelay' => 5,

        // Automatically clear cache when elements are saved
        'clearCacheAutomatically' => true,

        // Refresh cache after clearing (warm it back up)
        'refreshCacheAutomaticallyForGlobals' => true,

        // Generate transforms immediately (prevent slow first loads)
        'generateTransformsBeforePageLoad' => true,

        // Cache static include tags
        'cacheIncludeStaticContent' => true,

        // SSI (Server Side Includes) for dynamic content
        'ssiEnabled' => false,

        // Output comments in cached pages (for debugging)
        'outputComments' => App::env('BLITZ_DEBUG') ?? false,

        // Send X-Powered-By header
        'sendPoweredByHeader' => false,

        // Hints for intelligent purging
        'hintsEnabled' => true,

        // Debug mode
        'debug' => App::env('BLITZ_DEBUG') ?? false,
    ],

    'staging' => [
        'cachingEnabled' => true,
        'outputComments' => true,
        'debug' => true,
    ],

    'production' => [
        'cachingEnabled' => true,
        'outputComments' => false,
        'debug' => false,
        
        // Production-specific optimizations
        'compressCachedValues' => true,
        
        // Generate transforms before serving pages
        'generateTransformsBeforePageLoad' => true,
    ],
];
