<?php

/**
 * Khaneh Romantic — Read-only access to the WordPress/WooCommerce database
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Table names are given without the prefix (posts, postmeta, wc_orders…); the "wordpress"
 * connection adds it (WP_DB_PREFIX). Nothing here ever writes to WordPress.
 */

namespace Modules\WordPressImport\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class Source
{
    private ?bool $hpos = null;

    /** @var array<string, mixed> */
    private array $options = [];

    public function db(): ConnectionInterface
    {
        return DB::connection('wordpress');
    }

    public function table(string $table): Builder
    {
        return $this->db()->table($table);
    }

    public function hasTable(string $table): bool
    {
        try {
            return $this->db()->getSchemaBuilder()->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    /** Connection works and looks like WordPress. */
    public function check(): ?string
    {
        try {
            $this->table('options')->where('option_name', 'siteurl')->value('option_value');

            return null;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }

    public function option(string $name, mixed $default = null): mixed
    {
        if (! array_key_exists($name, $this->options)) {
            $value = $this->table('options')->where('option_name', $name)->value('option_value');
            $this->options[$name] = $value === null ? $default : self::unserialize($value);
        }

        return $this->options[$name];
    }

    /** WooCommerce «High-Performance Order Storage» (orders in wc_orders instead of posts). */
    public function hpos(): bool
    {
        if ($this->hpos === null) {
            $authoritative = $this->option('woocommerce_custom_orders_table_enabled');
            $this->hpos = $this->hasTable('wc_orders') && ($authoritative === 'yes' || $authoritative === null && $this->table('wc_orders')->exists());
        }

        return $this->hpos;
    }

    /**
     * Post meta for many posts at once.
     *
     * @param  list<int>  $ids
     * @return array<int, array<string, mixed>> post id => [meta_key => value]
     */
    public function postMeta(array $ids, ?array $keys = null): array
    {
        return $this->metaFrom('postmeta', 'post_id', $ids, $keys);
    }

    /** @return array<int, array<string, mixed>> */
    public function userMeta(array $ids, ?array $keys = null): array
    {
        return $this->metaFrom('usermeta', 'user_id', $ids, $keys);
    }

    /** @return array<int, array<string, mixed>> */
    public function commentMeta(array $ids, ?array $keys = null): array
    {
        return $this->metaFrom('commentmeta', 'comment_id', $ids, $keys);
    }

    /** @return array<int, array<string, mixed>> */
    public function orderMeta(array $ids, ?array $keys = null): array
    {
        return $this->hpos() ? $this->metaFrom('wc_orders_meta', 'order_id', $ids, $keys) : $this->postMeta($ids, $keys);
    }

    /** @return array<int, array<string, mixed>> */
    public function itemMeta(array $ids): array
    {
        return $this->metaFrom('woocommerce_order_itemmeta', 'order_item_id', $ids, null);
    }

    private function metaFrom(string $table, string $column, array $ids, ?array $keys): array
    {
        $out = [];
        foreach (array_chunk(array_values(array_unique($ids)), 500) as $chunk) {
            $this->table($table)->whereIn($column, $chunk)
                ->when($keys, fn ($q) => $q->whereIn('meta_key', $keys))
                ->orderBy(['usermeta' => 'umeta_id', 'wc_orders_meta' => 'id'][$table] ?? 'meta_id')
                ->get([$column, 'meta_key', 'meta_value'])
                ->each(function ($row) use (&$out, $column) {
                    $out[(int) $row->{$column}][$row->meta_key] = self::unserialize($row->meta_value);
                });
        }

        return $out;
    }

    /**
     * Terms of a taxonomy, keyed by term_taxonomy_id: [term_id, name, slug, parent (term_id), description, count].
     *
     * @return Collection<int, object>
     */
    public function terms(string $taxonomy): Collection
    {
        return $this->table('term_taxonomy as tt')
            ->join('terms as t', 't.term_id', '=', 'tt.term_id')
            ->where('tt.taxonomy', $taxonomy)
            ->orderBy('tt.parent')->orderBy('t.term_id')
            ->get(['tt.term_taxonomy_id', 't.term_id', 't.name', 't.slug', 'tt.parent', 'tt.description', 'tt.count'])
            ->keyBy('term_id');
    }

    /** @return array<int, list<int>> object id => term ids of that taxonomy */
    public function objectTerms(string $taxonomy, array $objectIds): array
    {
        $out = [];
        foreach (array_chunk(array_values(array_unique($objectIds)), 500) as $chunk) {
            $this->table('term_relationships as tr')
                ->join('term_taxonomy as tt', 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
                ->where('tt.taxonomy', $taxonomy)->whereIn('tr.object_id', $chunk)
                ->orderBy('tr.term_order')
                ->get(['tr.object_id', 'tt.term_id'])
                ->each(function ($row) use (&$out) {
                    $out[(int) $row->object_id][] = (int) $row->term_id;
                });
        }

        return $out;
    }

    /** @return array<int, array<string, mixed>> term id => meta */
    public function termMeta(array $termIds): array
    {
        return $this->hasTable('termmeta') ? $this->metaFrom('termmeta', 'term_id', $termIds, null) : [];
    }

    /** First existing taxonomy among candidates (brand plugins differ). */
    public function firstTaxonomy(array $candidates): ?string
    {
        foreach ($candidates as $taxonomy) {
            if ($this->table('term_taxonomy')->where('taxonomy', $taxonomy)->exists()) {
                return $taxonomy;
            }
        }

        return null;
    }

    public static function unserialize(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }
        if (preg_match('/^(a|O|s|i|d|b):[0-9:]/', $value) || $value === 'b:0;' || $value === 'N;') {
            $data = @unserialize($value, ['allowed_classes' => false]);
            if ($data !== false || $value === 'b:0;') {
                return $data;
            }
        }

        return $value;
    }
}
