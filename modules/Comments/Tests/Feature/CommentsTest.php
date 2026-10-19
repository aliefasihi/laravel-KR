<?php

/**
 * Blog comments: moderation, replies (visitor and shop), likes, login rule, admin, inbox record.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Comments\Tests\Feature;

use App\Core\Settings\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Modules\Comments\Filament\Resources\Comments\Pages\ListComments;
use Modules\Comments\Livewire\CommentsSection;
use Modules\Comments\Models\Comment;
use Modules\Content\Models\Post;
use Modules\Customer\Models\Customer;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use RefreshDatabase;

    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->post = Post::create(['slug' => 'how-to-choose', 'title' => 'چطور قابلمه بخریم', 'body_html' => '<p>متن</p>', 'status' => 'published', 'published_at' => now()->subDay()]);
    }

    private function section()
    {
        $section = Livewire::test(CommentsSection::class, ['model' => $this->post]);
        $this->travel(5)->seconds();

        return $section;
    }

    public function test_visitor_comment_waits_for_approval_then_shows_with_shop_reply(): void
    {
        $this->get('/blog/how-to-choose')->assertOk()->assertSee('نظرات')->assertSee('هنوز نظری ثبت نشده است.');

        $this->section()->call('submit')->assertHasErrors(['name', 'body'])
            ->set('name', 'نگار')->set('mobile', '۰۹۳۵۱۱۱۲۲۳۳')->set('body', 'برای القایی هم مناسب است؟')
            ->call('submit')->assertHasNoErrors()->assertSee('پس از بررسی منتشر می‌شود');

        $comment = Comment::query()->sole();
        $this->assertSame('pending', $comment->status);
        $this->assertSame('09351112233', $comment->mobile);
        $this->assertDatabaseHas('form_submissions', ['form' => 'post_comment', 'subject_id' => $comment->id]);
        $this->assertDatabaseHas('contacts', ['mobile' => '09351112233', 'name' => 'نگار']);
        $this->get('/blog/how-to-choose')->assertDontSee('برای القایی');

        // admin: approve by replying as the shop
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin/comments')->assertOk()->assertSee('برای القایی');
        Livewire::test(ListComments::class)->assertSet('activeTab', 'pending')
            ->callTableAction('reply', $comment, ['body' => 'بله، کف القایی دارد.']);
        $this->assertSame('approved', $comment->fresh()->status);
        $reply = Comment::query()->where('parent_id', $comment->id)->sole();
        $this->assertTrue($reply->is_staff);

        $this->get('/blog/how-to-choose')->assertSee('برای القایی هم مناسب است؟')->assertSee('پاسخ خانه رمانتیک')->assertSee('بله، کف القایی دارد.')->assertSee('نظرات');

        // visitor reply + like (once)
        $section = $this->section()->call('reply', $comment->id)->assertSee('پاسخ به نگار')
            ->set('name', 'سارا')->set('body', 'ممنون، من هم همین سؤال را داشتم.')->call('submit');
        $this->assertSame($comment->id, Comment::query()->where('name', 'سارا')->value('parent_id'));
        $section->call('like', $comment->id)->call('like', $comment->id);
        $this->assertSame(1, $comment->fresh()->likes);
    }

    public function test_moderation_settings_and_login_rule(): void
    {
        Settings::set(['comments.moderation' => 'customers']);
        $this->section()->set('name', 'مهمان')->set('body', 'نظر مهمان')->call('submit');
        $this->assertSame('pending', Comment::query()->where('name', 'مهمان')->value('status'));

        $customer = Customer::create(['mobile' => '09121234567', 'first_name' => 'علی', 'last_name' => 'فصیحی']);
        Auth::guard('customer')->login($customer);
        $this->section()->assertSet('name', 'علی فصیحی')->set('body', 'نظر مشتری')->call('submit')->assertSee('نظر شما منتشر شد');
        $this->assertSame('approved', Comment::query()->where('customer_id', $customer->id)->value('status'));

        Auth::guard('customer')->logout();
        Settings::set(['comments.require_login' => true]);
        $this->get('/blog/how-to-choose')->assertSee('برای نوشتن نظر، اول وارد شوید.')->assertSee('نظر مشتری');

        // bots
        Settings::set(['comments.require_login' => false, 'comments.moderation' => 'none']);
        Livewire::test(CommentsSection::class, ['model' => $this->post])->set('name', 'bot')->set('body', 'spam spam')->call('submit');
        $this->assertFalse(Comment::query()->where('name', 'bot')->exists());
    }
}
