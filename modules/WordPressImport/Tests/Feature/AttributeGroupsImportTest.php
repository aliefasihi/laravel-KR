<?php

/**
 * Attribute groups from the WordPress engine (kr_attr_group + kr_attrs / kr_groups / kr_inherit):
 * groups with ordered, flagged attributes; categories linked; «محصول » prefix dropped.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\WordPressImport\Tests\Feature;

use App\Core\Support\PersianText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Attributes\Models\AttributeGroup;
use Modules\Attributes\Models\ProductAttribute;
use Modules\Attributes\Support\AttributeTemplate;
use Modules\Catalog\Models\Category;
use Modules\WordPressImport\Support\Importer;
use Modules\WordPressImport\Support\Report;
use Tests\TestCase;

class AttributeGroupsImportTest extends TestCase
{
    use RefreshDatabase;
    use WooStoreFixture;

    public function test_groups_flags_and_category_links(): void
    {
        $wp = $this->site();
        $wp->db()->table('woocommerce_attribute_taxonomies')->insert(['attribute_name' => 'color01', 'attribute_label' => 'محصول رنگ بدنه']);
        $wp->db()->table('woocommerce_attribute_taxonomies')->insert(['attribute_name' => 'material', 'attribute_label' => 'جنس']);
        $wp->term(60, 'pa_color01', 'نقره‌ای', 'silver');
        $wp->term(61, 'pa_material', 'استیل', 'steel');
        $wp->term(62, 'pa_material', 'گرانیت', 'granite');

        // two meta shapes the engine may have saved: rows with flags, and a taxonomy => flags map
        $wp->term(70, 'kr_attr_group', 'عمومی آشپزخانه', 'kitchen-general', 0, ['kr_attrs' => [
            ['tax' => 'pa_material', 'req' => 1, 'var' => 0, 'multi' => 0],
            ['tax' => 'pa_color01', 'req' => 0, 'var' => 1, 'multi' => 0],
            ['tax' => 'pa_unknown'],
        ]]);
        $wp->term(71, 'kr_attr_group', 'قابلمه', 'pots', 0, ['kr_attrs' => json_encode(['pa_material' => ['multi' => true]])]);
        DB::connection('wordpress')->table('termmeta')->insert([
            ['term_id' => 20, 'meta_key' => 'kr_groups', 'meta_value' => serialize([70])],
            ['term_id' => 21, 'meta_key' => 'kr_groups', 'meta_value' => 'pots'],
            ['term_id' => 21, 'meta_key' => 'kr_inherit', 'meta_value' => '1'],
        ]);

        $report = app(Importer::class)->run(['taxonomies'], new Report(false));

        $color = ProductAttribute::query()->where('slug', 'color01')->sole();
        $this->assertSame('رنگ بدنه', $color->name);   // «محصول » prefix dropped

        $general = AttributeGroup::query()->where('name', 'عمومی آشپزخانه')->sole();
        $items = $general->items()->get();
        $this->assertSame(['material', 'color01'], $items->map(fn ($i) => $i->attribute->slug)->all());
        $this->assertTrue($items[0]->is_required);
        $this->assertTrue($items[1]->is_variation);
        $log = collect($report->log)->pluck('message')->implode(' | ');
        $this->assertStringContainsString('pa_unknown', $log);
        $this->assertStringContainsString('۲ گروه', PersianText::toPersianDigits($log));

        $kitchen = Category::query()->where('legacy_term_id', 20)->sole();
        $pots = Category::query()->where('legacy_term_id', 21)->sole();
        $this->assertSame(['عمومی آشپزخانه'], $kitchen->attributeGroups()->pluck('name')->all());
        $this->assertSame(['قابلمه'], $pots->attributeGroups()->pluck('name')->all());

        // the pots category: inherited general attributes first, its own after; flags OR-ed
        $template = app(AttributeTemplate::class)->forCategory($pots->id);
        $this->assertSame(['جنس', 'رنگ بدنه'], $template->map(fn ($i) => $i['attribute']->name)->values()->all());
        $this->assertTrue($template->first()['multiple']);
        $this->assertTrue($template->first()['required']);

        // running again changes nothing
        app(Importer::class)->run(['taxonomies'], new Report(false));
        $this->assertSame(2, AttributeGroup::query()->count());
        $this->assertSame(2, $general->items()->count());
    }
}
