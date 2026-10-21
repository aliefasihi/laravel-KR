<?php

/**
 * Khaneh Romantic — Keep models' options in step with the attribute library
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * A model stores its variation choice by name, {«رنگ»: «طلایی»} (that is what the cart, orders
 * and the product page show). Renaming the attribute («رنگ» → «رنگ بدنه») or one of its values
 * («طلایی» → «طلایی براق») in the panel renames it in every model too — otherwise those models
 * would silently lose their colour in the admin form and in the category filters.
 */

namespace Modules\Attributes\Support;

use Modules\Catalog\Models\ProductVariant;

final class VariantOptions
{
    public static function renameAttribute(string $from, string $to): int
    {
        return self::each(function (array $options) use ($from, $to) {
            if (! array_key_exists($from, $options) || $from === $to) {
                return null;
            }
            $renamed = [];
            foreach ($options as $key => $value) {
                $renamed[$key === $from ? $to : $key] = $value;   // keeps the order
            }

            return $renamed;
        });
    }

    public static function renameValue(string $attribute, string $from, string $to): int
    {
        return self::each(function (array $options) use ($attribute, $from, $to) {
            if (($options[$attribute] ?? null) !== $from || $from === $to) {
                return null;
            }
            $options[$attribute] = $to;

            return $options;
        });
    }

    /** @param  callable(array): ?array  $change  null = leave this model alone */
    private static function each(callable $change): int
    {
        $changed = 0;
        ProductVariant::query()->whereNotNull('options')->select(['id', 'options'])->chunkById(500, function ($variants) use ($change, &$changed) {
            foreach ($variants as $variant) {
                $options = $change((array) $variant->options);
                if ($options !== null) {
                    ProductVariant::query()->whereKey($variant->id)->update(['options' => json_encode($options, JSON_UNESCAPED_UNICODE)]);
                    $changed++;
                }
            }
        });

        return $changed;
    }
}
