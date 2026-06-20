<?php

declare(strict_types=1);

namespace Webkernel\StdPix;

/**
 * Centralized source for Webkernel icon collection paths.
 * Used by blade-icons config for the "default" set and other icon consumers.
 */
final class GetIcons
{
    /**
     * Return the list of icon directory paths (project-relative form).
     * These are suitable to be passed to blade-icons "path"/"paths" and then
     * resolved via Application::basePath().
     * @return array<int,string>
     */
    public static function paths(): array
    {
        return [
            webkernel_package('standard-pix', 'res/icons/custom', relative: true),
            webkernel_package('standard-pix', 'res/icons/lucide', relative: true),
            webkernel_package('standard-pix', 'res/icons/simple-icons', relative: true),
        ];
    }

    /**
     * Return paths as array, with optional extra paths appended.
     * Usage in config files:
     *   use Webkernel\StdPix\GetIcons;
     *   "path" => GetIcons::array(),
     * @param $extra array<int,string>
     * @return array<int,string>
     */
    public static function array(array $extra = []): array
    {
        $all = array_merge(self::paths(), $extra);

        // Deduplicate while preserving order
        $seen = [];
        $result = [];
        foreach ($all as $p) {
            $norm = rtrim((string) $p, '/');
            if ($norm !== '' && !isset($seen[$norm])) {
                $seen[$norm] = true;
                $result[] = $norm;
            }
        }

        return $result;
    }
}
