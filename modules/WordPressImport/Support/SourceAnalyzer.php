<?php

/**
 * Khaneh Romantic — Reads a WordPress database and works out how to import it
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Site address, currency, order storage (HPOS), brand plugin and its brands, WooCommerce order
 * statuses (custom ones too), permalinks, counts per step… The admin page shows these and
 * pre-fills the import settings from them.
 */

namespace Modules\WordPressImport\Support;

use Illuminate\Support\Str;
use Modules\Order\Models\Order;

class SourceAnalyzer
{
    public const CURRENCIES = [
        'IRT' => 'تومان (× ۱۰)',
        'IRR' => 'ریال (بدون تبدیل)',
        'IRHT' => 'هزار تومان (× ۱۰٬۰۰۰)',
        'IRHR' => 'هزار ریال (× ۱٬۰۰۰)',
    ];

    public function __construct(private Source $wp) {}

    public function analyze(): array
    {
        $home = rtrim((string) ($this->wp->option('home') ?: $this->wp->option('siteurl')), '/');
        $currency = strtoupper((string) $this->wp->option('woocommerce_currency', 'IRT'));
        $permalinks = (array) $this->wp->option('woocommerce_permalinks', []);
        try {
            $brandTaxonomies = $this->brandTaxonomies();
        } catch (\Throwable) {
            $brandTaxonomies = [];
        }

        $try = function (callable $fn, mixed $default) {
            try {
                return $fn();
            } catch (\Throwable) {
                return $default;   // a table this WordPress doesn't have
            }
        };

        return [
            'site' => [
                'name' => html_entity_decode((string) $this->wp->option('blogname', '')),
                'home' => $home,
                'woocommerce' => (string) $this->wp->option('woocommerce_version', ''),
                'prefix' => (string) config('database.connections.wordpress.prefix'),
            ],
            'currency' => $currency,
            'hpos' => $try(fn () => $this->wp->hpos(), false),
            'counts' => $this->counts($try),
            'brand_taxonomies' => $brandTaxonomies,
            'brands' => collect(array_keys($brandTaxonomies))->mapWithKeys(fn ($t) => [$t => $try(fn () => $this->brands($t), [])])->all(),
            'attributes' => $try(fn () => $this->wp->table('woocommerce_attribute_taxonomies')->orderBy('attribute_id')
                ->pluck('attribute_label', 'attribute_name')->map(fn ($l) => html_entity_decode((string) $l))->all(), []),
            'order_statuses' => $try(fn () => $this->orderStatuses(), []),
            'payment_methods' => $try(fn () => $this->paymentMethods(), []),
            'permalinks' => [
                'post' => (string) $this->wp->option('permalink_structure', ''),
                'product_base' => rawurldecode((string) ($permalinks['product_base'] ?? '/product')),
                'category_base' => trim(rawurldecode((string) ($permalinks['category_base'] ?? '') ?: 'product-category'), '/'),
            ],
            'plugins' => [
                'digits' => $try(fn () => $this->wp->table('usermeta')->whereIn('meta_key', ['digits_phone', 'digits_phone_no'])->exists(), false),
                'rank_math' => $try(fn () => $this->wp->table('postmeta')->where('meta_key', 'like', 'rank_math_%')->exists(), false),
                'yoast' => $try(fn () => $this->wp->table('postmeta')->where('meta_key', 'like', '_yoast_wpseo_%')->exists(), false),
            ],
        ];
    }

    /** Settings the page starts with (the admin can change every one). */
    public static function defaults(array $detected): array
    {
        $taxonomy = array_key_first($detected['brand_taxonomies'] ?? []) ?? '';
        $map = config('modules.wordpressimport.order_statuses', []);

        return [
            'legacy_base_url' => $detected['site']['home'] ?: config('modules.wordpressimport.legacy_base_url'),
            'currency' => array_key_exists($detected['currency'] ?? '', self::CURRENCIES) ? $detected['currency'] : 'IRT',
            'brand_taxonomy' => $taxonomy,
            'brand_slugs' => collect($detected['brands'][$taxonomy] ?? [])->mapWithKeys(fn ($b, $id) => [$id => $b['suggested']])->all(),
            'order_statuses' => collect($detected['order_statuses'] ?? [])->mapWithKeys(fn ($n, $status) => [$status => $map[$status] ?? self::guessStatus($status)])->all(),
            'unmanaged_stock' => (int) config('modules.wordpressimport.unmanaged_stock', 50),
            'review_default_rating' => config('modules.wordpressimport.review_default_rating', 5),
        ];
    }

    public static function guessStatus(string $status): string
    {
        $s = strtolower(preg_replace('/^wc-/', '', $status));

        return match (true) {
            str_contains($s, 'refund') || str_contains($s, 'marjoo') || str_contains($s, 'return') => 'refunded',
            str_contains($s, 'cancel') || str_contains($s, 'laghv') || str_contains($s, 'lagv') => 'cancelled',
            str_contains($s, 'fail') => 'failed',
            str_contains($s, 'complet') || str_contains($s, 'deliver') || str_contains($s, 'tahvil') || str_contains($s, 'done') => 'completed',
            str_contains($s, 'ship') || str_contains($s, 'sent') || str_contains($s, 'send') || str_contains($s, 'ersal') || str_contains($s, 'post') => 'shipped',
            str_contains($s, 'hold') || str_contains($s, 'review') || str_contains($s, 'wait') || str_contains($s, 'barrasi') => 'on_hold',
            $s === 'pending' || str_contains($s, 'unpaid') => 'pending',
            default => 'processing',
        };
    }

    /** @return array<string, int> brand-like taxonomy => terms, most terms first */
    private function brandTaxonomies(): array
    {
        $known = (array) config('modules.wordpressimport.brand_taxonomies', []);
        $rows = $this->wp->table('term_taxonomy')
            ->where(fn ($q) => $q->whereIn('taxonomy', $known)->orWhere('taxonomy', 'like', '%brand%'))
            ->selectRaw('taxonomy, count(*) as n')->groupBy('taxonomy')->pluck('n', 'taxonomy')
            ->map(fn ($n) => (int) $n)->sortDesc()->all();

        return $rows;
    }

    /** @return array<int, array{name: string, slug: string, suggested: string, products: int}> */
    public function brands(string $taxonomy): array
    {
        $fixed = (array) config('modules.wordpressimport.brand_slugs', []);
        $used = [];

        return $this->wp->terms($taxonomy)->sortByDesc('count')->mapWithKeys(function ($term) use ($fixed, &$used) {
            $name = html_entity_decode((string) $term->name);
            $slug = rawurldecode((string) $term->slug);
            $suggested = $fixed[$name]['slug'] ?? (preg_match('/^[a-z0-9-]+$/i', $slug) ? strtolower($slug) : (Str::slug(Str::ascii($name)) ?: 'brand-'.$term->term_id));
            for ($base = $suggested, $i = 2; isset($used[$suggested]); $i++) {
                $suggested = $base.'-'.$i;
            }
            $used[$suggested] = true;

            return [(int) $term->term_id => ['name' => $name, 'slug' => $slug, 'suggested' => $suggested, 'products' => (int) $term->count]];
        })->all();
    }

    private function counts(callable $try): array
    {
        $posts = fn (string $type, array $statuses = ['publish', 'draft', 'pending', 'private', 'future']) => $try(fn () => $this->wp->table('posts')
            ->where('post_type', $type)->whereIn('post_status', $statuses)->count(), 0);

        return [
            'products' => $posts('product'),
            'variations' => $posts('product_variation', ['publish', 'private']),
            'posts' => $posts('post'),
            'pages' => $posts('page', ['publish']),
            'attachments' => $try(fn () => $this->wp->table('posts')->where('post_type', 'attachment')->count(), 0),
            'users' => $try(fn () => $this->wp->table('users')->count(), 0),
            'orders' => $try(fn () => array_sum($this->orderStatuses()), 0),
            'coupons' => $posts('shop_coupon'),
            'reviews' => $try(fn () => $this->wp->table('comments as c')->join('posts as p', 'p.ID', '=', 'c.comment_post_ID')
                ->where('p.post_type', 'product')->where('c.comment_parent', 0)->whereIn('c.comment_approved', ['0', '1'])->count(), 0),
        ];
    }

    /** @return array<string, int> */
    private function orderStatuses(): array
    {
        $skip = ['trash', 'auto-draft', 'wc-checkout-draft', 'draft'];
        $query = $this->wp->hpos()
            ? $this->wp->table('wc_orders')->where('type', 'shop_order')->whereNotIn('status', $skip)->selectRaw('status as s, count(*) as n')->groupBy('status')
            : $this->wp->table('posts')->where('post_type', 'shop_order')->whereNotIn('post_status', $skip)->selectRaw('post_status as s, count(*) as n')->groupBy('post_status');

        return $query->pluck('n', 's')->map(fn ($n) => (int) $n)->sortDesc()->all();
    }

    /** @return array<string, string> */
    private function paymentMethods(): array
    {
        $rows = $this->wp->hpos()
            ? $this->wp->table('wc_orders')->where('type', 'shop_order')->whereNotNull('payment_method')->distinct()->limit(20)->pluck('payment_method_title', 'payment_method')
            : $this->wp->table('postmeta')->where('meta_key', '_payment_method_title')->distinct()->limit(20)->pluck('meta_value', 'meta_value');

        return $rows->map(fn ($t) => html_entity_decode((string) $t))->filter()->all();
    }

    /** Labels for our order statuses (for the mapping selects). */
    public static function ourStatuses(): array
    {
        return class_exists(Order::class) ? Order::STATUSES : [];
    }
}
