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
            $brandsPath = webkernel_package('standard-pix', 'res/brands', relative: false);
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

        $paths = \Webkernel\StdPix\GetIcons::paths();

        return $paths;
    }
}

if (!function_exists('webkernel_grab_icon')) {
    /**
     * Grab an SVG icon from the Webkernel icon collections and inject class and style.
     *
     * @param string $filename Icon name without extension (e.g. "arrow-right").
     * @param string $class    Optional CSS classes to inject.
     * @param string $style    Optional inline styles to inject.
     * @return string|null     Raw SVG markup, or null if not found.
     */
    function webkernel_grab_icon(string $filename, string $class = '', string $style = ''): ?string
    {
        foreach (webkernel_svg_collection_paths() as $rel) {
            $path = project_root($rel);
            $full = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename . '.svg';

            if (is_file($full)) {
                $svg = file_get_contents($full);
                if ($svg === false) {
                    return null;
                }

                // Préparation des attributs à injecter
                $inject = '';
                if ($class !== '') {
                    $inject .= ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"';
                }
                if ($style !== '') {
                    $inject .= ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"';
                }

                // Injection ultra-rapide juste après la balise ouvrante <svg
                if ($inject !== '') {
                    $svg = substr_replace($svg, $inject, 4, 0);
                }

                return $svg;
            }
        }

        return null;
    }
}

// Eagerly initialize branding assets (and register their /__webkernel-app/ routes)
// as soon as this file is loaded via Composer. This ensures routes exist when
// WebkernelRouter dispatch runs after the autoloader (in public/index.php).
if (php_sapi_name() !== 'cli') {
    webkernel_branding_store();
}
