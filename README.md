# webkernel/std-svg-collection

Standalone collection of +6000 SVG icons (Lucide, Simple Icons, and custom)
for the Webkernel platform.

**No Laravel dependency.** Works in any PHP 8.4+ project.

## Installation

```bash
composer require webkernel/std-svg-collection
```

## Usage

```php
$svg = webkernel_grab_icon('arrow-right');

if ($svg !== null) {
    echo $svg;
}
```

## Icon sets

| Set          | Directory                     | Source                                            |
| ------------ | ----------------------------- | ------------------------------------------------- |
| Lucide       | `resources/svg/lucide/`       | [lucide.dev](https://lucide.dev)                  |
| Simple Icons | `resources/svg/simple-icons/` | [simpleicons.org](https://simpleicons.org)        |
| Custom       | `resources/svg/custom/`       | Project-specific icons, override any of the above |

Search order: `custom` → `lucide` → `simple-icons`. The first match wins.

## Functions

### `webkernel_grab_icon(string $filename): ?string`

Returns raw SVG markup for the given icon name (without extension), or `null`
if not found.

## License

EPL-2.0 — see upstream icon set licenses for icon-specific terms (Lucide: ISC,
Simple Icons: CC0).
