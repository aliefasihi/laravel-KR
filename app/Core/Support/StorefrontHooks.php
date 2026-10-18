<?php

/**
 * Khaneh Romantic — Places in the storefront layout where modules add their own bits
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   StorefrontHooks::add('header.actions', 'customer::partials.account-button');
 *
 *   @storefrontHook('header.actions')
 *
 * Hooks in the layout: head.end, body.start, header.actions, drawer.footer, footer.links, body.end.
 * A switched-off module never registers, so its pieces simply aren't there.
 */

namespace App\Core\Support;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

class StorefrontHooks
{
    /** @var array<string, list<array{0: int, 1: string|Closure}>> */
    private static array $hooks = [];

    /** @param  string|Closure(): (string|Htmlable|View|null)  $content  a view name or a closure */
    public static function add(string $hook, string|Closure $content, int $sort = 50): void
    {
        self::$hooks[$hook][] = [$sort, $content];
    }

    public static function has(string $hook): bool
    {
        return ! empty(self::$hooks[$hook]);
    }

    public static function render(string $hook, array $data = []): Htmlable
    {
        $items = self::$hooks[$hook] ?? [];
        usort($items, fn ($a, $b) => $a[0] <=> $b[0]);

        $html = '';
        foreach ($items as [, $content]) {
            $out = is_string($content) ? view($content, $data) : $content($data);
            $html .= match (true) {
                $out instanceof Htmlable => $out->toHtml(),
                $out instanceof View => $out->render(),
                default => (string) $out,
            };
        }

        return new HtmlString($html);
    }

    public static function flush(): void
    {
        self::$hooks = [];
    }
}
