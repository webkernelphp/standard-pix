<?php declare(strict_types=1);
// @package webkernel/stdpix/load.stdpix.functions.php
use Webkernel\StdPix\BrandingStore;
if (!function_exists('webkernel_branding_store')) {
    /**
     * Returns the singleton BrandingStore, initializing it on first call.
     * @return BrandingStore
     */
    function webkernel_branding_store(): BrandingStore
    {
        static $store = null;

        if ($store !== null) {
            return $store;
        }

        $store = new BrandingStore();

        try {
            $brandsPath = webkernel_package('std-functions', 'resources/brands', relative: false);
            foreach (['webkernel', 'numerimondes', 'thebestrecruit'] as $brand) {
                $store->loadFromDirectory("{$brandsPath}/{$brand}", $brand);
            }
        } catch (\Throwable) {
            // Brand assets are non-critical — silenced.
        }

        $store->registerRoutes();

        return $store;
    }
}

if (!function_exists('webkernel_branding_url')) {
    /**
     * Return the WebkernelRouter URL for a registered branding asset.
     *
     * @param string $key Branding asset key.
     * @return string     Absolute URL served by WebkernelRouter.
     */
    function webkernel_branding_url(string $key): string
    {
        return webkernel_branding_store()->url($key);
    }
}


if (!function_exists('webkernel_svg_collection_paths')) {
    /**
     * Returns the ordered list of directories searched by webkernel_grab_icon().
     * Evaluated on first call, not at include time — BASE_PATH must be defined
     * before this function is called, not before the file is loaded.
     *
     * @return list<string>
     */
    function webkernel_svg_collection_paths(): array
    {
        static $paths = null;

        if ($paths !== null) {
            return $paths;
        }

        $paths = [
            __DIR__ . '/res/icons/custom',
            __DIR__ . '/res/icons/lucide',
            __DIR__ . '/res/icons/simple-icons'
        ];

        return $paths;
    }
}

if (!function_exists('webkernel_grab_icon')) {
    /**
     * Grab an SVG icon from the Webkernel icon collections.
     *
     * Search order: custom -> lucide -> simple-icons. First match wins,
     * so dropping a file in resources/custom/ overrides any built-in icon
     * with the same name.
     *
     * @param string $filename Icon name without extension (e.g. "arrow-right").
     * @return string|null     Raw SVG markup, or null if not found.
     */
    function webkernel_grab_icon(string $filename): ?string
    {
        foreach (webkernel_svg_collection_paths() as $path) {
            $full = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename . '.svg';
            if (is_file($full)) {
                return file_get_contents($full) ?: null;
            }
        }

        return null;
    }
}
