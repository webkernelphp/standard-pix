<?php declare(strict_types=1);
namespace Webkernel\StdPix;
/**
 * Internal branding registry and asset manager.
 *
 * Holds branding assets (logos, icons, etc.) in memory as base64-encoded
 * binary data, and registers HTTP routes via WebkernelRouter to serve
 * them with proper caching headers.
 *
 * Assets are also cached on disk (storage/framework/cache/branding/)
 * as decoded binary files so repeated requests skip base64_decode entirely.
 * Response time is reported in the X-Branding-Time header as nanoseconds.
 */
final class BrandingStore
{
    /** @var array<string, array<string, array{format: string, data: string}>> */
    private array $store = [];

    private readonly string $cacheDir;

    public function __construct()
    {
        $this->cacheDir = webkernel_cache_path('branding');
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Register a branding asset and return its data URI.
     */
    public function add(string $brand, string $key, string $format, string $base64): string
    {
        $this->store[$brand][$key] = ['format' => $format, 'data' => $base64];
        $this->writeToDiskCache($key, $format, $base64);
        return "data:image/{$format};base64,{$base64}";
    }

    /**
     * Return the data URI for a registered asset, or an empty string if not found.
     */
    public function dataUri(string $key): string
    {
        $brand = explode('-', $key, 2)[0];
        $asset = $this->store[$brand][$key] ?? null;
        return $asset !== null ? "data:image/{$asset['format']};base64,{$asset['data']}" : '';
    }

    /**
     * Return the WebkernelRouter URL for a registered asset, or an empty string if not found.
     */
    public function url(string $key): string
    {
        $brand = explode('-', $key, 2)[0];
        $asset = $this->store[$brand][$key] ?? null;
        if ($asset === null) {
            return '';
        }
        return WebkernelRouter::url("branding/{$brand}/{$key}") . '?v=' . substr(md5($asset['data']), 0, 8);
    }

    /**
     * Register HTTP routes for every loaded branding asset.
     */
    public function registerRoutes(): void
    {
        foreach ($this->store as $brand => $assets) {
            foreach ($assets as $key => $asset) {
                $cacheDir = $this->cacheDir;
                WebkernelRouter::registerClosure("branding/{$brand}/{$key}", static function () use ($asset, $key, $cacheDir): never {
                    $start = hrtime(true); // nanoseconds

                    $etag = '"' . substr(md5($asset['data']), 0, 16) . '"';

                    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
                        $elapsed = hrtime(true) - $start;
                        header('X-Branding-Time: ' . $elapsed . 'ns');
                        http_response_code(304);
                        exit(0);
                    }

                    // Try disk cache first — avoids base64_decode on every request
                    $cachePath = $cacheDir . DIRECTORY_SEPARATOR . md5($key) . '.' . $asset['format'];
                    if (is_file($cachePath)) {
                        $binary = file_get_contents($cachePath);
                    } else {
                        $binary = base64_decode($asset['data']);
                        file_put_contents($cachePath, $binary, LOCK_EX);
                    }

                    $elapsed = hrtime(true) - $start;

                    header('Content-Type: image/' . $asset['format']);
                    header('Content-Length: ' . strlen($binary));
                    header('Cache-Control: public, max-age=31536000, immutable');
                    header('ETag: ' . $etag);
                    header('X-Branding-Time: ' . $elapsed . 'ns');
                    echo $binary;
                    exit(0);
                });
            }
        }
    }

    /**
     * Load all asset definition files from a directory.
     *
     * Each file must return an array with 'key', 'format', and 'data' keys.
     */
    public function loadFromDirectory(string $directory, string $brand): void
    {
        if (!is_dir($directory)) {
            return;
        }
        foreach (glob("{$directory}/*.brand.php") as $file) {
            $asset = require $file;
            if (is_array($asset) && isset($asset['key'], $asset['format'], $asset['data'])) {
                $this->add($brand, $asset['key'], $asset['format'], $asset['data']);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Write decoded binary to disk cache if not already present.
     */
    private function writeToDiskCache(string $key, string $format, string $base64): void
    {
        $cachePath = $this->cacheDir . DIRECTORY_SEPARATOR . md5($key) . '.' . $format;
        if (!is_file($cachePath)) {
            file_put_contents($cachePath, base64_decode($base64), LOCK_EX);
        }
    }
}
