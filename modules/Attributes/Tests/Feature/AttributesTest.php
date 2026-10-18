<?php

/**
 * Category templates (groups + inheritance), product values, specs table, category filters
 * and variation attributes on product models.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Attributes\Tests\Feature;

use App\Core\Support\StoreCache;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Attributes\Filament\Forms\VariationFields;
use Modules\Attributes\Support\AttributeTemplate;
use Modules\Attributes\Support\ProductAttributeValues;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use Modules\Catalog\Models\Product;
use Modules\Search\Support\ProductIndexer;
use Modules\Search\Tests\Feature\SearchTestCase;

class AttributesTest extends SearchTestCase
{
    use AttributeFixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->catalog();
        $this->attributes();
    }

    private function find(string $sku): Product
    {
        return Product::query()->where('sku', $sku)->firstOrFail();
    }

    public function test_category_template_inherits_parent_groups(): void
    {
        $templates = app(AttributeTemplate::class);

        $this->assertSame([$this->material->id, $this->color->id], $templates->forCategory($this->kitchen->id)->keys()->all());
        $this->assertSame([$this->material->id, $this->color->id, $this->pieces->id], $templates->forCategory($this->cookware->id)->keys()->all());
        $this->assertTrue($templates->forCategory($this->cookware->id)[$this->color->id]['variation']);
        $this->assertTrue($templates->forCategory($this->cookware->id)[$this->material->id]['required']);
        $this->assertSame([], $templates->forCategory($this->decor->id)->keys()->all());

        // switching inheritance off keeps only the category's own groups
        DB::table('attribute_category_settings')->insert(['category_id' => $this->cookware->id, 'inherit' => false]);
        StoreCache::flush();
        $this->assertSame([$this->pieces->id], $templates->forCategory($this->cookware->id)->keys()->all());

        // the same attribute in two groups: flags are merged
        $this->cookwareGroup->items()->create(['attribute_id' => $this->material->id, 'sort' => 2, 'is_multiple' => true]);
        DB::table('attribute_category_settings')->delete();
        StoreCache::flush();
        $material = $templates->forCategory($this->cookware->id)[$this->material->id];
        $this->assertTrue($material['multiple']);
        $this->assertTrue($material['required']);
    }

    public function test_values_are_validated_and_shown_as_a_specs_table(): void
    {
        $set = $this->find('GK-7710');
        $this->setValues($set, $this->material, ['گرانیت']);
        $this->setValues($set, $this->pieces, ['۱۰']);
        $this->setValues($set, $this->color, ['طوسی', 'مشکی']);

        // a value of another attribute is ignored
        app(ProductAttributeValues::class)->sync($set, $this->material->id, [$this->valueId($this->material, 'سنگ'), $this->valueId($this->color, 'طلایی')]);
        $this->assertSame([$this->valueId($this->material, 'سنگ')], app(ProductAttributeValues::class)->forProduct($set->id)[$this->material->id]);

        $specs = app(ProductAttributeValues::class)->specs($set->load('categories'));
        $this->assertSame([
            ['attribute_id' => $this->material->id, 'name' => 'جنس', 'value' => 'سنگ'],
            ['attribute_id' => $this->color->id, 'name' => 'رنگ', 'value' => 'طوسی، مشکی'],
            ['attribute_id' => $this->pieces->id, 'name' => 'تعداد پارچه', 'value' => '۱۰ پارچه'],
        ], $specs);

        $this->get($set->url())->assertOk()
            ->assertSeeInOrder(['جنس', 'سنگ', 'رنگ', 'طوسی، مشکی', 'تعداد پارچه', '۱۰ پارچه'])
            ->assertSee('"additionalProperty"', false);

        // hidden attributes stay out of the table
        $this->pieces->update(['is_visible' => false]);
        $this->assertNotContains('تعداد پارچه', array_column(app(ProductAttributeValues::class)->specs($set), 'name'));
    }

    public function test_category_page_filters_by_attributes_with_counts(): void
    {
        $this->setValues($this->find('GK-7710'), $this->material, ['گرانیت']);
        $this->setValues($this->find('GK-2801'), $this->material, ['گرانیت']);
        $this->setValues($this->find('3217073'), $this->material, ['سنگ']);
        $this->setValues($this->find('GK-7710'), $this->color, ['مشکی']);
        $this->setValues($this->find('3217073'), $this->color, ['طوسی']);
        app(ProductIndexer::class)->rebuild();

        $granite = $this->valueId($this->material, 'گرانیت');
        $stone = $this->valueId($this->material, 'سنگ');
        $grey = $this->valueId($this->color, 'طوسی');

        // the kitchen page shows its template's filterable attributes (not «تعداد پارچه»), with counts
        $this->getRaw('/product-category/kitchen/')->assertOk()
            ->assertSee('name="attr[material][]" value="'.$granite.'"', false)
            ->assertSee('name="attr[color][]" value="'.$grey.'"', false)
            ->assertDontSee('name="attr[pieces][]"', false)
            ->assertDontSee('value="'.$this->valueId($this->material, 'فلز').'"', false) // no product has it
            ->assertSeeInOrder(['گرانیت', '۲', 'سنگ', '۱']);

        $this->getRaw('/product-category/kitchen/?attr[material][]='.$granite)->assertOk()
            ->assertSee('سرویس قابلمه ۱۰ پارچه گرانیتی')
            ->assertSee('تابه گرانیتی سایز ۲۸')
            ->assertDontSee('قندان سنگی طوسی مدل بلوط')
            ->assertSeeInOrder(['سنگ', '۱']); // other values of the same attribute keep their counts (OR within one attribute)

        // values of one attribute = OR, different attributes = AND
        $this->getRaw('/product-category/kitchen/?attr[material][]='.$granite.'&attr[material][]='.$stone.'&attr[color][]='.$grey)->assertOk()
            ->assertSee('قندان سنگی طوسی مدل بلوط')
            ->assertDontSee('سرویس قابلمه ۱۰ پارچه گرانیتی');

        // values of another attribute or unknown slugs are ignored
        $this->getRaw('/product-category/kitchen/?attr[material][]='.$grey.'&attr[nope][]=1')->assertOk()
            ->assertSee('سرویس قابلمه ۱۰ پارچه گرانیتی')
            ->assertSee('قندان سنگی طوسی مدل بلوط');

        // attribute labels are searchable
        $this->get('/search?q='.urlencode('مشکی'))->assertOk()->assertSee('سرویس قابلمه ۱۰ پارچه گرانیتی');
    }

    public function test_variation_values_come_from_the_models(): void
    {
        $set = $this->find('GK-7710');
        $set->variants()->first()->update(['options' => ['رنگ' => 'مشکی']]);
        $set->variants()->create(['sku' => 'GK-7710-GLD', 'price' => 99_000_000, 'stock' => 2, 'options' => ['رنگ' => 'طلایی']]);
        $set->variants()->create(['sku' => 'GK-7710-OLD', 'price' => 99_000_000, 'stock' => 0, 'is_active' => false, 'options' => ['رنگ' => 'طوسی']]);

        $this->assertSame([$this->color->id], array_keys(VariationFields::attributes($set)));

        VariationFields::syncProduct($set);
        $this->assertEqualsCanonicalizing(
            [$this->valueId($this->color, 'مشکی'), $this->valueId($this->color, 'طلایی')],
            app(ProductAttributeValues::class)->forProduct($set->id)[$this->color->id],
        );

        // form ⇄ options mapping used by the models table in the admin
        $data = VariationFields::toOptions(['sku' => 'X', 'variation_'.$this->color->id => 'طوسی'], $set);
        $this->assertSame(['رنگ' => 'طوسی'], $data['options']);
        $this->assertArrayNotHasKey('variation_'.$this->color->id, $data);
        $this->assertSame('طلایی', VariationFields::fromOptions(['options' => ['رنگ' => 'طلایی']], $set)['variation_'.$this->color->id]);
    }

    public function test_admin_product_form_saves_attribute_values(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $set = $this->find('GK-7710');
        $this->setValues($set, $this->pieces, ['۱۰']);

        // required «جنس» from the parent's group
        Livewire::test(EditProduct::class, ['record' => $set->getRouteKey()])
            ->assertFormFieldExists('attribute_'.$this->material->id)
            ->assertFormFieldExists('attribute_'.$this->pieces->id)
            ->assertFormSet(['attribute_'.$this->pieces->id => $this->valueId($this->pieces, '۱۰')])
            ->fillForm(['attribute_'.$this->material->id => null])
            ->call('save')
            ->assertHasFormErrors(['attribute_'.$this->material->id => 'required']);

        Livewire::test(EditProduct::class, ['record' => $set->getRouteKey()])
            ->fillForm([
                'attribute_'.$this->material->id => $this->valueId($this->material, 'گرانیت'),
                'attribute_'.$this->color->id => [$this->valueId($this->color, 'طوسی')],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $values = app(ProductAttributeValues::class)->forProduct($set->id);
        $this->assertSame([$this->valueId($this->material, 'گرانیت')], $values[$this->material->id]);
        $this->assertSame([$this->valueId($this->color, 'طوسی')], $values[$this->color->id]);

        // once the product has models with a colour, the models decide its colours
        $set->variants()->first()->update(['options' => ['رنگ' => 'مشکی']]);
        Livewire::test(EditProduct::class, ['record' => $set->getRouteKey()])->call('save')->assertHasNoFormErrors();
        $this->assertSame([$this->valueId($this->color, 'مشکی')], app(ProductAttributeValues::class)->forProduct($set->id)[$this->color->id]);
    }

    public function test_admin_models_pick_variation_values_from_the_list(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $set = $this->find('GK-7710');

        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditProduct::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'sku' => 'GK-7710-GLD', 'stock' => 3, 'price' => 9_900_000, 'is_active' => true,
                'variation_'.$this->color->id => 'طلایی',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame(['رنگ' => 'طلایی'], $set->variants()->where('sku', 'GK-7710-GLD')->value('options'));
        $this->assertContains($this->valueId($this->color, 'طلایی'), app(ProductAttributeValues::class)->forProduct($set->id)[$this->color->id]);
    }
}
