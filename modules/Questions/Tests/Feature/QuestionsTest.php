<?php

/**
 * Q&A: a guest asks, the question waits for an answer, the shop answers in the panel → it goes live
 * under the product and the asker gets one SMS; helpful votes once per visitor.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Questions\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Modules\Catalog\Models\Product;
use Modules\Order\Tests\Feature\ShopTestCase;
use Modules\Questions\Filament\Resources\Questions\Pages\ListQuestions;
use Modules\Questions\Livewire\QuestionsSection;
use Modules\Questions\Models\Question;

class QuestionsTest extends ShopTestCase
{
    public function test_ask_answer_publish_and_sms(): void
    {
        Auth::guard('customer')->logout();
        Settings::set(['questions.answered_template' => 'qa-answered']);
        $pot = Product::query()->where('sku', 'GK-7710')->first();

        $this->get($pot->url())->assertOk()->assertSee('پرسش و پاسخ')->assertSee('هنوز سؤالی پرسیده نشده');

        Livewire::test(QuestionsSection::class, ['productId' => $pot->id])
            ->set('body', 'درب شیشه‌ای هم دارد؟')->set('name', 'مریم')->set('mobile', '۰۹۱۲۳۳۳۴۴۵۵')
            ->call('ask')->assertHasNoErrors()->assertSet('sent', true);

        $question = Question::query()->sole();
        $this->assertSame(['pending', '09123334455', true], [$question->status, $question->mobile, $question->notify]);
        $this->get($pot->url())->assertDontSee('درب شیشه‌ای هم دارد؟');   // not before the answer

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ListQuestions::class)->assertCanSeeTableRecords([$question])
            ->callTableAction('answer', $question, ['answer' => 'بله، کف این سرویس مغناطیسی است.'])->assertHasNoTableActionErrors();

        $question->refresh();
        $this->assertSame('published', $question->status);
        $this->assertCount(1, $this->sms);
        [$mobile, $template, $values] = $this->sms[0];
        $this->assertSame(['09123334455', 'qa-answered', $pot->name], [$mobile, $template, $values[0]]);
        $this->assertStringContainsString('#questions', $values[1]);

        $this->get($pot->url())->assertSee('درب شیشه‌ای هم دارد؟')->assertSee('کف این سرویس مغناطیسی است')->assertSee('پرسش و پاسخ (۱)');

        // editing the answer doesn't SMS again; helpful counts once
        Livewire::test(ListQuestions::class)->set('tableFilters.status.value', 'published')
            ->callTableAction('answer', $question, ['answer' => 'بله، روی همه‌ی اجاق‌ها از جمله القایی.']);
        $this->assertCount(1, $this->sms);
        Livewire::test(QuestionsSection::class, ['productId' => $pot->id])->call('helpful', $question->id)->call('helpful', $question->id);
        $this->assertSame(1, $question->fresh()->helpful);
    }

    public function test_validation_and_guests_switch(): void
    {
        Auth::guard('customer')->logout();
        $pot = Product::query()->where('sku', 'GK-7710')->first();
        Livewire::test(QuestionsSection::class, ['productId' => $pot->id])->set('body', 'کوتاه')->call('ask')->assertHasErrors(['body', 'name']);

        Settings::set(['questions.allow_guests' => false]);
        Livewire::test(QuestionsSection::class, ['productId' => $pot->id])->assertSee('ورود و پرسیدن سؤال');
    }
}
