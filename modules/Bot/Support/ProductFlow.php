<?php

/**
 * Khaneh Romantic — «محصول جدید» in the bot
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 * Send photos, then the details in any form — text, «key: value» lines, or a Persian voice note.
 * With AI on, the voice is turned into text and the name, brand, category, prices, stock, code,
 * attributes and an SEO description are filled from it (and from the photos); without AI (or when it
 * fails) the «key: value» lines are read. Whatever is still missing is asked step by step with
 * buttons. Then a preview with edit buttons → «انتشار»: the product, its image folder, the photos.
 * The draft belongs to the person, not the app: start in Telegram, finish in Bale.
 */

namespace Modules\Bot\Support;

use App\Core\Modules\Modules;
use App\Core\Support\Money;
use App\Core\Support\PersianText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Attributes\Models\AttributeValue;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Attributes\Support\AttributeTemplate;
use Modules\Bot\Models\BotDraft;
use Modules\Bot\Models\BotOperator;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use RuntimeException;
use Throwable;

final class ProductFlow
{
    private const PAGE = 8;

    public function __construct(private BotOperator $op, private Out $out) {}

    public function start(?Incoming $first = null): void
    {
        $draft = BotDraft::query()->create(['operator_id' => $this->op->id, 'channel' => $this->out->channel->name, 'data' => ['texts' => []]]);
        $this->op->forceFill(['draft_id' => $draft->id])->save();
        $this->op->goTo('draft', ['ask' => null]);

        if ($first) {
            $this->input($first);

            return;
        }
        $this->out->say(
            "📦 محصول جدید\n\n۱. عکس‌ها را بفرستید (یکی یا چندتا با هم).\n۲. اطلاعات را هر طور راحتید بفرستید: متن، ویس، یا خط به خط مثل:\nنام: …\nقیمت: …\nموجودی: …\n\nیا «قدم‌به‌قدم» را بزنید تا تک‌تک بپرسم.",
            Out::buttons([[['▶️ قدم‌به‌قدم بپرس', 'd:'.$draft->id.':next'], ['❌ لغو', 'd:'.$draft->id.':cancel']]]),
        );
    }

    public function draft(): ?BotDraft
    {
        $draft = $this->op->draft_id ? BotDraft::query()->find($this->op->draft_id) : null;

        return $draft && $draft->status === 'open' ? $draft : null;
    }

    public function open(BotDraft $draft): void
    {
        $this->op->forceFill(['draft_id' => $draft->id])->save();
        $this->op->goTo('draft', ['ask' => null]);
        $this->next($draft);
    }

    // ── what the person sent ───────────────────────────────────────────

    public function input(Incoming $in): void
    {
        $draft = $this->draft();
        if (! $draft) {
            $this->op->goTo('idle');
            $this->out->menu('این پیش‌نویس دیگر باز نیست.');

            return;
        }

        if ($in->photo) {
            $this->addPhoto($draft, $in);
            if ($in->text) {
                $this->text($draft, $in->text);
            }

            return;
        }
        if ($in->voice) {
            $this->voice($draft, $in);

            return;
        }
        if ($in->text !== null) {
            $ask = $this->op->ctx('ask');
            $ask ? $this->answer($draft, $ask, $in->text) : $this->text($draft, $in->text);
        }
    }

    private function addPhoto(BotDraft $draft, Incoming $in): void
    {
        $bytes = $this->out->channel->download($in->photo);
        if (! $bytes) {
            $this->out->say('⚠️ دریافت عکس نشد؛ لطفاً دوباره بفرستید.');

            return;
        }
        $count = DB::transaction(function () use ($draft, $in, $bytes) {
            $locked = BotDraft::query()->lockForUpdate()->find($draft->id);
            $photos = (array) $locked->photos;
            $path = 'bot/drafts/'.$draft->id.'/'.(count($photos) + 1).'-'.substr(md5($in->photo), 0, 6).'.jpg';
            Storage::disk('local')->put($path, $bytes);
            $photos[] = ['path' => $path];
            $locked->update(['photos' => $photos]);

            return count($photos);
        });

        // several photos sent together arrive one by one: answer once per album
        $group = $in->mediaGroup;
        if ($group && $this->op->ctx('group') === $group) {
            return;
        }
        $this->op->goTo('draft', ['group' => $group], merge: true);
        if ($this->op->ctx('ask') === 'photos') {
            $this->op->goTo('draft', ['ask' => null], merge: true);
            $this->next($draft->refresh());

            return;
        }
        $this->out->say(
            '📷 عکس رسید'.($count > 1 || $group ? '' : ' ('.PersianText::toPersianDigits($count).')').'. عکس دیگری هست بفرستید؛ بعد اطلاعات محصول را (متن یا ویس) بفرستید.',
            Out::buttons([[['▶️ ادامه', 'd:'.$draft->id.':next']]]),
        );
    }

    private function voice(BotDraft $draft, Incoming $in): void
    {
        if (! ProductAi::available()) {
            $this->out->say('🎙 برای ویس، «هوش مصنوعی» باید در پنل روشن باشد. فعلاً متنی بفرستید.');

            return;
        }
        $this->out->say('🎙 دارم گوش می‌دهم…');
        $bytes = $this->out->channel->download($in->voice);
        $path = 'bot/drafts/'.$draft->id.'/voice-'.substr(md5((string) $in->voice), 0, 8).'.ogg';
        $text = null;
        if ($bytes) {
            Storage::disk('local')->put($path, $bytes);
            $text = app(ProductAi::class)->transcribe(Storage::disk('local')->path($path));
            Storage::disk('local')->delete($path);
        }
        if (! $text) {
            $this->out->say('⚠️ تبدیل ویس به متن الان نشد؛ دوباره بفرستید یا بنویسید.');

            return;
        }
        $this->out->say('📝 «'.$text.'»');
        $ask = $this->op->ctx('ask');
        $ask ? $this->answer($draft, $ask, $text) : $this->text($draft, $text);
    }

    /** free text (or a transcript): read it, with AI if on, then ask what's missing */
    private function text(BotDraft $draft, string $text): void
    {
        $texts = (array) $draft->get('texts', []);
        $texts[] = mb_substr($text, 0, 3000);
        $draft->put(['texts' => $texts]);

        $typed = ProductText::parse($text);
        $explicit = $typed['_explicit'] ?? [];
        unset($typed['_explicit']);
        // «key: value» lines are what the person meant (AI won't change them); loose guesses aren't
        $draft->put(array_intersect_key($typed, array_flip($explicit)), byHand: true);
        $draft->put(array_filter(array_diff_key($typed, array_flip($explicit)), fn ($key) => ! $draft->isLocked($key), ARRAY_FILTER_USE_KEY));

        if (ProductAi::available()) {
            $this->out->say('✨ دارم اطلاعات را می‌خوانم…');
            try {
                $ai = app(ProductAi::class)->extract($draft->refresh());
            } catch (Throwable) {
                $ai = null;
            }
            if ($ai) {
                $draft->put(array_filter($ai, fn ($key) => ! $draft->isLocked($key), ARRAY_FILTER_USE_KEY));
            } elseif (! $typed) {
                $this->out->say('هوش مصنوعی الان جواب نداد؛ قدم‌به‌قدم می‌پرسم.');
            }
        }
        $this->next($draft->refresh());
    }

    // ── asking ─────────────────────────────────────────────────────────

    public function next(BotDraft $draft): void
    {
        $missing = ProductPublisher::missing($draft);
        if ($missing === []) {
            $this->preview($draft);

            return;
        }
        $field = $missing[0];
        if ($field === 'brand' && $draft->get('brand_text') && ! $draft->get('brand_en')) {
            $field = 'brand_en';
        }
        $this->ask($draft, $field);
    }

    public function ask(BotDraft $draft, string $field, array $extra = []): void
    {
        $this->op->goTo('draft', ['ask' => $field] + $extra, merge: true);
        $id = $draft->id;
        $back = $draft->status === 'open' && ProductPublisher::missing($draft) === [] ? [['⬅️ بازگشت به پیش‌نمایش', "d:$id:preview"]] : [];

        match (true) {
            $field === 'photos' => $this->out->say('🖼 یک یا چند عکس از محصول بفرستید.'),
            $field === 'name' => $this->out->say('✏️ نام کامل محصول؟', Out::buttons([$back])),
            $field === 'brand' => $this->brands($draft, (int) ($extra['page'] ?? 0)),
            $field === 'brand_en' => $this->out->say('🏷 برند «'.$draft->get('brand_text').'» در سایت نیست؛ می‌سازمش. نام انگلیسی‌اش؟ (برای آدرس صفحه، مثل Goldkish)', Out::buttons([[['انتخاب از برندهای سایت', "d:$id:brands:0"]]])),
            $field === 'category_ids' => $this->categories($draft, $extra['parent'] ?? null),
            $field === 'price' => $this->out->say("💰 قیمت به تومان؟\nمثلاً ۲۴۵۰۰۰۰ یا «۲ میلیون و ۴۵۰ هزار»", Out::buttons([$back])),
            $field === 'sale_price' => $this->out->say('🏷 قیمت حراج به تومان؟ (۰ = بدون حراج)', Out::buttons([$back])),
            $field === 'stock' => $this->out->say('📦 موجودی؟', Out::buttons([
                [['۰', "d:$id:stock:0"], ['۱', "d:$id:stock:1"], ['۲', "d:$id:stock:2"], ['۳', "d:$id:stock:3"], ['۵', "d:$id:stock:5"], ['۱۰', "d:$id:stock:10"]], $back,
            ])),
            $field === 'sku' => $this->out->say('🔢 کد محصول (SKU، با حروف لاتین)؟', Out::buttons([
                [['⚙️ خودکار: '.ProductPublisher::suggestSku($draft->get('brand_id'), $draft->get('brand_en')), "d:$id:sku:auto"]], $back,
            ])),
            $field === 'description' => $this->out->say('📝 توضیحات محصول را بنویسید (یا ویس بفرستید).', Out::buttons([$back])),
            str_starts_with($field, 'attr:') => $this->out->say('✍️ مقدار «'.$this->attributeName((int) substr($field, 5)).'» را بنویسید.', Out::buttons([[['⬅️ بازگشت', "d:$id:attrs"]]])),
            default => $this->preview($draft),
        };
    }

    private function answer(BotDraft $draft, string $field, string $text): void
    {
        $text = trim($text);
        $error = null;
        switch (true) {
            case $field === 'name':
                mb_strlen($text) >= 3 ? $draft->put(['name' => mb_substr($text, 0, 200)], true) : $error = 'نام کوتاه است.';
                break;
            case $field === 'brand':
                if ($brand = ProductText::brand($text)) {
                    $draft->put(['brand_id' => $brand->id], true);
                } else {
                    $draft->put(['brand_text' => mb_substr($text, 0, 60)], true);
                }
                break;
            case $field === 'brand_en':
                $en = trim(preg_replace('/[^A-Za-z0-9 \-]/', '', PersianText::toLatinDigits($text)));
                $en !== '' ? $draft->put(['brand_en' => $en], true) : $error = 'فقط حروف لاتین، مثل Goldkish';
                break;
            case $field === 'category_ids':
                $category = ProductText::category($text);
                if ($category) {
                    $draft->put(['category_ids' => [$category->id]], true);
                } else {
                    $error = 'دسته‌ای با این نام پیدا نشد؛ از دکمه‌ها انتخاب کنید.';
                }
                break;
            case in_array($field, ['price', 'sale_price'], true):
                $value = Numbers::toman($text);
                if ($value === null || ($field === 'price' && $value < 1000)) {
                    $error = 'عدد قیمت را متوجه نشدم (به تومان).';
                } elseif ($field === 'sale_price' && $value === 0) {
                    $data = (array) $draft->data;
                    unset($data['sale_price']);
                    $draft->update(['data' => $data]);
                } elseif ($field === 'sale_price' && $value >= (int) $draft->get('price')) {
                    $error = 'قیمت حراج باید از قیمت اصلی کمتر باشد.';
                } else {
                    $draft->put([$field => $value], true);
                }
                break;
            case $field === 'stock':
                $n = Numbers::count($text);
                if ($n === null) {
                    $error = 'یک عدد بفرستید.';
                } else {   // not put(): 0 is a real answer
                    $draft->update(['data' => ['stock' => $n] + (array) $draft->data, 'locked' => array_values(array_unique([...(array) $draft->locked, 'stock']))]);
                }
                break;
            case $field === 'sku':
                $sku = ProductText::sku($text);
                if (! $sku) {
                    $error = 'کد را با حروف و عدد لاتین بفرستید.';
                } elseif (ProductPublisher::skuTaken($sku)) {
                    $error = 'این کد مال محصول دیگری است؛ کد دیگری بفرستید.';
                } else {
                    $draft->put(['sku' => $sku], true);
                }
                break;
            case $field === 'description':
                $draft->put(['description' => mb_substr($text, 0, 5000)], true);
                break;
            case str_starts_with($field, 'attr:'):
                $attrs = (array) $draft->get('attributes_text', []);
                $attrs[$this->attributeName((int) substr($field, 5))] = mb_substr($text, 0, 120);
                $draft->put(['attributes_text' => $attrs], true);
                $this->op->goTo('draft', ['ask' => null], merge: true);
                $this->attributes($draft->refresh());

                return;
            case $field === 'photos':
                $error = 'منتظر عکس هستم 🙂';
                break;
        }
        if ($error) {
            $this->out->say('⚠️ '.$error);

            return;
        }
        $this->op->goTo('draft', ['ask' => null], merge: true);
        $this->next($draft->refresh());
    }

    // ── buttons ────────────────────────────────────────────────────────

    /** d:{draft}:{action}[:{arg}] */
    public function button(string $data): void
    {
        [, $id, $action, $arg] = array_pad(explode(':', $data, 4), 4, null);
        $draft = BotDraft::query()->where('operator_id', $this->op->id)->find((int) $id);
        if (! $draft || $draft->status !== 'open') {
            $this->out->say('این پیش‌نویس دیگر باز نیست.');

            return;
        }
        if ($this->op->draft_id !== $draft->id || $this->op->state !== 'draft') {   // pressed on an older message
            $this->op->forceFill(['draft_id' => $draft->id])->save();
            $this->op->goTo('draft', ['ask' => null]);
        }

        match ($action) {
            'next' => $this->next($draft),
            'preview' => $this->preview($draft),
            'cancel' => $this->cancel($draft),
            'publish' => $this->publish($draft, true),
            'save' => $this->publish($draft, false),
            'edit' => $this->ask($draft, (string) $arg),
            'brands' => $this->ask($draft, 'brand', ['page' => (int) $arg]),
            'brand' => $this->setAndNext($draft, ['brand_id' => (int) $arg]),
            'newbrand' => $this->newBrand(),
            'cats' => $this->ask($draft, 'category_ids', ['parent' => $arg === '' || $arg === null ? null : (int) $arg]),
            'cat' => $this->setAndNext($draft, ['category_ids' => array_values(array_unique([(int) $arg, ...array_map('intval', (array) $draft->get('category_ids', []))]))], replaceCategories: true),
            'stock' => $this->setStock($draft, (int) $arg),
            'sku' => $this->setAndNext($draft, ['sku' => ProductPublisher::suggestSku($draft->get('brand_id'), $draft->get('brand_en'))]),
            'attrs' => $this->attributes($draft),
            'attr' => $this->attributeValues($draft, (int) $arg),
            'val' => $this->toggleValue($draft, (string) $arg),
            'attrnew' => $this->ask($draft, 'attr:'.(int) $arg),
            default => $this->preview($draft),
        };
    }

    private function newBrand(): void
    {
        $this->op->goTo('draft', ['ask' => 'brand'], merge: true);
        $this->out->say('🏷 نام فارسی برند جدید را بفرستید.');
    }

    private function setAndNext(BotDraft $draft, array $values, bool $replaceCategories = false): void
    {
        if ($replaceCategories) {
            $values['category_ids'] = [reset($values['category_ids'])];
        }
        $draft->put($values, true);
        $this->op->goTo('draft', ['ask' => null], merge: true);
        $this->next($draft->refresh());
    }

    private function setStock(BotDraft $draft, int $stock): void
    {
        $draft->update(['data' => ['stock' => $stock] + (array) $draft->data, 'locked' => array_values(array_unique([...(array) $draft->locked, 'stock']))]);
        $this->op->goTo('draft', ['ask' => null], merge: true);
        $this->next($draft->refresh());
    }

    // ── pickers ────────────────────────────────────────────────────────

    private function brands(BotDraft $draft, int $page): void
    {
        $brands = Brand::query()->orderBy('name_fa')->get(['id', 'name_fa']);
        $slice = $brands->slice($page * self::PAGE, self::PAGE)->values();
        $id = $draft->id;
        $rows = $slice->chunk(2)->map(fn ($pair) => $pair->map(fn ($b) => [$b->name_fa, "d:$id:brand:{$b->id}"])->values()->all())->values()->all();
        $nav = array_filter([
            $page > 0 ? ['« قبلی', "d:$id:brands:".($page - 1)] : null,
            ($page + 1) * self::PAGE < $brands->count() ? ['بعدی »', "d:$id:brands:".($page + 1)] : null,
        ]);
        $rows[] = array_values($nav);
        $rows[] = [['➕ برند جدید', "d:$id:newbrand"]];
        $this->out->say('🏷 برند؟ (یا نامش را بنویسید)', Out::buttons($rows));
    }

    private function categories(BotDraft $draft, ?int $parent): void
    {
        $id = $draft->id;
        $children = Category::query()->where('parent_id', $parent)->orderBy('sort')->orderBy('name')->get(['id', 'name', 'parent_id']);
        $hasKids = Category::query()->whereIn('parent_id', $children->pluck('id'))->pluck('parent_id')->flip();
        $rows = $children->chunk(2)->map(fn ($pair) => $pair->map(fn ($c) => isset($hasKids[$c->id])
            ? ['📂 '.$c->name, "d:$id:cats:{$c->id}"]
            : [$c->name, "d:$id:cat:{$c->id}"])->values()->all())->values()->all();
        if ($parent) {
            $current = Category::query()->find($parent);
            $rows[] = [['✓ همین «'.$current?->name.'»', "d:$id:cat:$parent"], ['⬅️ بالا', "d:$id:cats:".($current?->parent_id ?? '')]];
        }
        $this->out->say('📂 دسته؟ (📂 = زیردسته دارد؛ یا نام دسته را بنویسید)', Out::buttons($rows));
    }

    private function attributes(BotDraft $draft): void
    {
        if (! Modules::enabled('Attributes')) {
            $this->preview($draft);

            return;
        }
        $id = $draft->id;
        $template = app(AttributeTemplate::class)->forCategories((array) $draft->get('category_ids', []));
        $attributes = $template->isNotEmpty()
            ? $template->map(fn ($r) => $r['attribute'])->values()
            : ProductAttribute::query()->orderBy('sort')->limit(16)->get();
        $set = $this->attributeSummary($draft);
        $rows = $attributes->chunk(2)->map(fn ($pair) => $pair->map(fn ($a) => [(isset($set[$a->name]) ? '✓ ' : '').$a->name, "d:$id:attr:{$a->id}"])->values()->all())->values()->all();
        $rows[] = [['✅ تمام', "d:$id:preview"]];
        $this->op->goTo('draft', ['ask' => null], merge: true);
        $this->out->say("🧩 ویژگی‌ها\n".($set ? implode("\n", array_map(fn ($k, $v) => "• $k: $v", array_keys($set), $set)) : 'هنوز چیزی انتخاب نشده.'), Out::buttons($rows));
    }

    private function attributeValues(BotDraft $draft, int $attributeId): void
    {
        $id = $draft->id;
        $chosen = array_map('intval', (array) ($draft->get('attributes', [])[$attributeId] ?? []));
        $values = AttributeValue::query()->where('attribute_id', $attributeId)->orderBy('sort')->orderBy('value')->limit(24)->get(['id', 'value']);
        $rows = $values->chunk(3)->map(fn ($chunk) => $chunk->map(fn ($v) => [(in_array($v->id, $chosen, true) ? '✓ ' : '').$v->value, "d:$id:val:{$attributeId}.{$v->id}"])->values()->all())->values()->all();
        $rows[] = [['✍️ مقدار جدید', "d:$id:attrnew:$attributeId"], ['⬅️ بازگشت', "d:$id:attrs"]];
        $this->out->say('«'.$this->attributeName($attributeId).'»: یکی یا چندتا را انتخاب کنید.', Out::buttons($rows));
    }

    private function toggleValue(BotDraft $draft, string $arg): void
    {
        [$attributeId, $valueId] = array_map('intval', explode('.', $arg) + [0, 0]);
        $attributes = (array) $draft->get('attributes', []);
        $list = array_map('intval', (array) ($attributes[$attributeId] ?? []));
        $list = in_array($valueId, $list, true) ? array_values(array_diff($list, [$valueId])) : [...$list, $valueId];
        $attributes[$attributeId] = $list;
        if ($list === []) {
            unset($attributes[$attributeId]);
        }
        $data = (array) $draft->data;
        $data['attributes'] = $attributes;
        $draft->update(['data' => $data]);
        $this->attributeValues($draft->refresh(), $attributeId);
    }

    /** @return array<string, string> attribute name => «value، value» (buttons + typed/AI) */
    private function attributeSummary(BotDraft $draft): array
    {
        $out = array_map('strval', (array) $draft->get('attributes_text', []));
        foreach ((array) $draft->get('attributes', []) as $attributeId => $valueIds) {
            $values = AttributeValue::query()->whereKey((array) $valueIds)->pluck('value')->implode('، ');
            if ($values !== '') {
                $out[$this->attributeName((int) $attributeId)] = $values;
            }
        }

        return $out;
    }

    private function attributeName(int $id): string
    {
        return (string) (Modules::enabled('Attributes') ? ProductAttribute::query()->whereKey($id)->value('name') : '');
    }

    // ── preview / publish ──────────────────────────────────────────────

    public function preview(BotDraft $draft): void
    {
        $this->op->goTo('draft', ['ask' => null], merge: true);
        $id = $draft->id;
        $brand = $draft->get('brand_id') ? Brand::query()->whereKey($draft->get('brand_id'))->value('name_fa') : ($draft->get('brand_text') ? $draft->get('brand_text').' (جدید)' : '—');
        $categories = Category::query()->whereKey((array) $draft->get('category_ids', []))->pluck('name')->implode('، ') ?: '—';
        $attrs = $this->attributeSummary($draft);
        $sale = $draft->get('sale_price');

        $caption = implode("\n", array_filter([
            '📦 '.$draft->title(),
            '🏷 برند: '.$brand,
            '📂 دسته: '.$categories,
            '💰 قیمت: '.($draft->get('price') ? Money::toman((int) $draft->get('price') * 10).' تومان' : '—').($sale ? '  ← حراج: '.Money::toman((int) $sale * 10) : ''),
            '📦 موجودی: '.($draft->get('stock') !== null ? PersianText::toPersianDigits((string) $draft->get('stock')) : '—'),
            '🔢 کد: '.($draft->get('sku') ?: '—'),
            $attrs ? '🧩 '.implode(' · ', array_map(fn ($k, $v) => "$k: $v", array_keys($attrs), $attrs)) : null,
            '📝 توضیحات: '.($draft->get('description') ? 'دارد' : 'ندارد'),
            '🖼 عکس: '.PersianText::toPersianDigits((string) count((array) $draft->photos)),
        ]));
        $missing = ProductPublisher::missing($draft);
        $rows = [
            $missing === [] ? [['✅ انتشار', "d:$id:publish"], ['💾 ذخیره بدون انتشار', "d:$id:save"]] : [['▶️ تکمیل اطلاعات', "d:$id:next"]],
            [['✏️ نام', "d:$id:edit:name"], ['✏️ قیمت', "d:$id:edit:price"], ['🏷 حراج', "d:$id:edit:sale_price"]],
            [['✏️ موجودی', "d:$id:edit:stock"], ['✏️ دسته', "d:$id:edit:category_ids"], ['✏️ برند', "d:$id:edit:brand"]],
            [['✏️ کد', "d:$id:edit:sku"], ['📝 توضیحات', "d:$id:edit:description"], ['🧩 ویژگی‌ها', "d:$id:attrs"]],
            [['🗑 لغو این محصول', "d:$id:cancel"]],
        ];
        $photo = $draft->photoPaths()[0] ?? null;
        $photo ? $this->out->photo($photo, $caption, Out::buttons($rows)) : $this->out->say($caption, Out::buttons($rows));
    }

    private function publish(BotDraft $draft, bool $active): void
    {
        $this->out->say($active ? '⏳ در حال انتشار…' : '⏳ در حال ذخیره…');
        try {
            $product = app(ProductPublisher::class)->publish($draft, $active);
        } catch (RuntimeException $e) {
            $this->out->say('⚠️ '.$e->getMessage());
            str_contains($e->getMessage(), 'کد') ? $this->ask($draft, 'sku') : $this->next($draft);

            return;
        }
        $this->op->forceFill(['draft_id' => null])->save();
        $this->op->goTo('idle');
        Storage::disk('local')->deleteDirectory('bot/drafts/'.$draft->id);

        $admin = rtrim((string) config('app.url'), '/').'/admin/products/'.$product->id.'/edit';
        $this->out->say(($active ? "✅ منتشر شد!\n" : "💾 ذخیره شد (منتشر نشده).\n").$product->name."\n\n🔗 ".url($product->url())."\n🛠 ویرایش در پنل: ".$admin, Out::menuKeyboard());
    }

    private function cancel(BotDraft $draft): void
    {
        $draft->update(['status' => 'cancelled']);
        Storage::disk('local')->deleteDirectory('bot/drafts/'.$draft->id);
        $this->op->forceFill(['draft_id' => null])->save();
        $this->op->goTo('idle');
        $this->out->menu('🗑 لغو شد.');
    }
}
