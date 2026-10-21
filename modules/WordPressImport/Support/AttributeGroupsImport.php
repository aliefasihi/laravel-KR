<?php

/**
 * Khaneh Romantic — Attribute groups from the WordPress engine (kr-attr-engine)
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * The WordPress site keeps its attribute groups in the «kr_attr_group» taxonomy:
 *   group term meta  «kr_attrs»   — ordered attributes with flags (required / variation / multiple)
 *   category meta    «kr_groups»  — the groups of that product category
 *                    «kr_inherit» — whether it inherits its parent's groups
 * Meta may be serialized PHP, JSON or a comma list, and an attribute may be named by taxonomy
 * (pa_material), attribute name (material) or label — all are understood. Without that taxonomy
 * this does nothing.
 */

namespace Modules\WordPressImport\Support;

use App\Core\Support\StoreCache;
use Illuminate\Support\Facades\DB;
use Modules\Attributes\Models\AttributeGroup;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Catalog\Models\Category;

final class AttributeGroupsImport
{
    public const TAXONOMY = 'kr_attr_group';

    private const FLAGS = [
        'is_required' => ['req', 'required', 'is_required', 'mandatory', 'r'],
        'is_variation' => ['var', 'variation', 'is_variation', 'v'],
        'is_multiple' => ['multi', 'multiple', 'is_multiple', 'm'],
    ];

    private const NAME_KEYS = ['tax', 'taxonomy', 'attr', 'attribute', 'slug', 'name', 'key', 'pa'];

    public function __construct(private Source $wp) {}

    public function run(Report $report): void
    {
        if (! $this->wp->table('term_taxonomy')->where('taxonomy', self::TAXONOMY)->exists()) {
            return;
        }

        $attributes = $this->attributeLookup();
        $terms = $this->wp->terms(self::TAXONOMY);
        $meta = $this->wp->termMeta($terms->keys()->all());
        $groups = [];   // wp term id => group id

        foreach ($terms as $term) {
            $rows = $this->rows($meta[$term->term_id]['kr_attrs'] ?? null);
            if ($report->dryRun) {
                continue;
            }

            $group = AttributeGroup::query()->firstOrCreate(['name' => html_entity_decode($term->name)], ['description' => $term->description ?: null]);
            $groups[(int) $term->term_id] = $group->id;
            $group->items()->delete();
            $sort = 0;
            $missing = [];
            foreach ($rows as [$name, $flags]) {
                $attributeId = $attributes[$this->norm($name)] ?? null;
                if (! $attributeId) {
                    $missing[] = $name;

                    continue;
                }
                if ($group->items()->where('attribute_id', $attributeId)->exists()) {
                    continue;
                }
                $group->items()->create(['attribute_id' => $attributeId, 'sort' => ++$sort] + $flags);
            }
            if ($missing) {
                $report->warn('taxonomies', 'گروه ویژگی «'.$term->name.'»: ویژگی پیدا نشد: '.implode('، ', array_slice($missing, 0, 5)));
            }
        }

        if ($report->dryRun) {
            $report->info('taxonomies', 'گروه‌های ویژگی: '.$terms->count().' گروه در وردپرس');

            return;
        }

        // categories → groups (+ inheritance)
        $categories = Category::query()->whereNotNull('legacy_term_id')->pluck('id', 'legacy_term_id')->all();
        $linked = 0;
        $slugs = $terms->mapWithKeys(fn ($t) => [rawurldecode($t->slug) => (int) $t->term_id])->all();
        foreach ($this->wp->termMeta(array_keys($categories)) as $termId => $catMeta) {
            $categoryId = $categories[$termId] ?? null;
            if (! $categoryId) {
                continue;
            }
            if (array_key_exists('kr_groups', $catMeta)) {
                $ids = [];
                foreach ($this->list($catMeta['kr_groups']) as $ref) {
                    $wpId = is_numeric($ref) ? (int) $ref : ($slugs[rawurldecode((string) $ref)] ?? null);
                    if ($wpId && isset($groups[$wpId])) {
                        $ids[] = $groups[$wpId];
                    }
                }
                Category::query()->find($categoryId)?->attributeGroups()->sync(array_values(array_unique($ids)));
                $linked++;
            }
            if (array_key_exists('kr_inherit', $catMeta)) {
                $inherit = ! in_array(strtolower(trim((string) $catMeta['kr_inherit'])), ['0', 'no', 'false', 'off', ''], true);
                DB::table('attribute_category_settings')->updateOrInsert(['category_id' => $categoryId], ['inherit' => $inherit]);
            }
        }

        StoreCache::flush();
        $report->info('taxonomies', 'گروه‌های ویژگی: '.count($groups).' گروه، به '.$linked.' دسته وصل شد');
    }

    /** @return list<array{0: string, 1: array<string, bool>}> attribute reference + flags, in order */
    private function rows(mixed $raw): array
    {
        $raw = Source::unserialize($raw);
        if (is_string($raw) && ($json = json_decode($raw, true)) !== null) {
            $raw = $json;
        }
        if (is_string($raw)) {
            $raw = $this->list($raw);
        }

        $rows = [];
        foreach ((array) $raw as $key => $item) {
            if (is_string($item) || is_numeric($item) || is_bool($item)) {
                // ['pa_material', …] or ['pa_color' => 'var', …]
                $rows[] = is_string($key) ? [$key, $this->flags([(string) $item => true])] : [(string) $item, $this->flags([])];

                continue;
            }
            if (! is_array($item)) {
                continue;
            }
            $name = null;
            foreach (self::NAME_KEYS as $nameKey) {
                if (isset($item[$nameKey]) && is_string($item[$nameKey]) && $item[$nameKey] !== '') {
                    $name = $item[$nameKey];
                    break;
                }
            }
            $name ??= is_string($key) ? $key : null;
            if ($name !== null) {
                $rows[] = [$name, $this->flags($item)];
            }
        }

        return $rows;
    }

    /** @return array<string, bool> */
    private function flags(array $item): array
    {
        $flags = [];
        foreach (self::FLAGS as $column => $keys) {
            $flags[$column] = false;
            foreach ($keys as $key) {
                if (array_key_exists($key, $item)) {
                    $flags[$column] = filter_var($item[$key], FILTER_VALIDATE_BOOLEAN) || $item[$key] === 1;
                    break;
                }
            }
        }

        return $flags;
    }

    /** @return list<string> */
    private function list(mixed $value): array
    {
        $value = Source::unserialize($value);
        if (is_string($value) && ($json = json_decode($value, true)) !== null) {
            $value = $json;
        }
        if (is_array($value)) {
            return array_values(array_map('strval', array_filter($value, fn ($v) => is_scalar($v) && $v !== '')));
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,،\s]+/u', (string) $value) ?: [])));
    }

    /** pa_material / material / «جنس» / «محصول جنس» → attribute id */
    private function attributeLookup(): array
    {
        $lookup = [];
        $wpNames = $this->wp->hasTable('woocommerce_attribute_taxonomies')
            ? $this->wp->table('woocommerce_attribute_taxonomies')->pluck('attribute_label', 'attribute_name')->all() : [];

        foreach (ProductAttribute::query()->get(['id', 'name', 'slug']) as $attribute) {
            $lookup[$this->norm($attribute->name)] = $attribute->id;
            $lookup[$this->norm($attribute->slug)] = $attribute->id;
        }
        foreach ($wpNames as $name => $label) {
            $id = $lookup[$this->norm(AttributeLabels::clean((string) $label))] ?? $lookup[$this->norm((string) $name)] ?? null;
            if ($id) {
                $lookup[$this->norm((string) $name)] = $id;
                $lookup[$this->norm('pa_'.$name)] = $id;
                $lookup[$this->norm((string) $label)] = $id;
            }
        }

        return $lookup;
    }

    private function norm(string $name): string
    {
        return mb_strtolower(trim(rawurldecode($name)));
    }
}
