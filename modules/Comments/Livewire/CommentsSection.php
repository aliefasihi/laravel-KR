<?php

/**
 * Khaneh Romantic — Comments under a blog post: list (with shop replies), reply, like, write
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Comments\Livewire;

use App\Core\Settings\Settings;
use App\Core\Support\PersianText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Comments\Models\Comment;
use Modules\Crm\Support\Forms;
use Modules\Customer\Support\Mobile;

class CommentsSection extends Component
{
    #[Locked]
    public string $type;

    #[Locked]
    public int $modelId;

    public int $perPage = 10;

    public ?int $replyTo = null;

    public string $name = '';

    public string $mobile = '';

    public string $email = '';

    public string $body = '';

    public string $website = '';   // honeypot

    #[Locked]
    public int $startedAt = 0;

    public ?string $notice = null;

    public function mount(Model $model): void
    {
        $this->type = $model->getMorphClass();
        $this->modelId = (int) $model->getKey();
        $this->startedAt = now()->getTimestamp();
        if ($customer = Auth::guard('customer')->user()) {
            $this->name = $customer->fullName();
            $this->mobile = $customer->mobile;
        }
    }

    private function query()
    {
        return Comment::query()->where('commentable_type', $this->type)->where('commentable_id', $this->modelId);
    }

    public function reply(int $id): void
    {
        $this->replyTo = $this->query()->approved()->whereNull('parent_id')->whereKey($id)->exists() ? $id : null;
        $this->notice = null;
    }

    public function cancelReply(): void
    {
        $this->replyTo = null;
    }

    public function loadMore(): void
    {
        $this->perPage += 10;
    }

    public function like(int $id): void
    {
        $liked = (array) session('comments.liked', []);
        if (in_array($id, $liked, true) || ! $this->query()->approved()->whereKey($id)->exists()) {
            return;
        }
        Comment::query()->whereKey($id)->increment('likes');
        session()->put('comments.liked', [...$liked, $id]);
    }

    public function submit(): void
    {
        $customer = Auth::guard('customer')->user();
        if (Settings::get('comments.require_login') && ! $customer) {
            return;
        }
        $askMobile = Settings::get('comments.ask_mobile', 'optional');
        $this->mobile = PersianText::toLatinDigits($this->mobile);
        $this->validate([
            'name' => ['required', 'string', 'max:60'],
            'mobile' => [$askMobile === 'required' && ! $customer ? 'required' : 'nullable', fn ($a, $v, $fail) => $v === '' || Mobile::normalize($v) ? null : $fail('شماره‌ی موبایل درست نیست.')],
            'email' => ['nullable', 'email', 'max:120'],
            'body' => ['required', 'string', 'min:3', 'max:3000'],
        ], [], ['name' => 'نام', 'mobile' => 'موبایل', 'email' => 'ایمیل', 'body' => 'متن نظر']);

        if ($this->website !== '' || now()->getTimestamp() - $this->startedAt < 3) {
            $this->notice = 'ممنون! نظر شما پس از بررسی منتشر می‌شود.';
            $this->reset(['body', 'replyTo']);

            return;
        }
        $key = 'comment:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            $this->addError('body', 'نظرهای زیادی فرستاده‌اید؛ کمی بعد دوباره امتحان کنید.');

            return;
        }
        RateLimiter::hit($key, 3600);

        $approved = match (Settings::get('comments.moderation', 'all')) {
            'none' => true,
            'customers' => (bool) $customer,
            default => false,
        };
        $parent = $this->replyTo ? $this->query()->approved()->whereNull('parent_id')->find($this->replyTo) : null;

        $comment = Comment::create([
            'commentable_type' => $this->type,
            'commentable_id' => $this->modelId,
            'parent_id' => $parent?->id,
            'customer_id' => $customer?->id,
            'name' => trim($this->name),
            'mobile' => Mobile::normalize($this->mobile),
            'email' => trim($this->email) ?: null,
            'body' => trim($this->body),
            'status' => $approved ? 'approved' : 'pending',
            'ip' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 250),
        ]);

        $title = $comment->commentable?->title ?? '';
        Forms::record('post_comment', array_filter([
            'name' => $comment->name, 'mobile' => $comment->mobile, 'email' => $comment->email,
            'post' => $title, 'reply_to' => $parent?->name, 'body' => $comment->body,
        ]), ['name' => $comment->name, 'mobile' => $comment->mobile, 'email' => $comment->email, 'customer_id' => $customer?->id], $comment, status: 'read');

        $this->notice = $approved ? 'نظر شما منتشر شد. ممنون!' : 'ممنون! نظر شما پس از بررسی منتشر می‌شود.';
        $this->reset(['body', 'replyTo']);
    }

    public function render()
    {
        $base = $this->query()->approved();
        $total = (clone $base)->count();
        $comments = (clone $base)->whereNull('parent_id')->latest('id')->limit($this->perPage)
            ->with(['replies' => fn ($q) => $q->approved()])->get();

        return view('comments::livewire.section', [
            'total' => $total,
            'comments' => $comments,
            'topLevel' => (clone $base)->whereNull('parent_id')->count(),
            'customer' => Auth::guard('customer')->user(),
            'liked' => (array) session('comments.liked', []),
            'replyingTo' => $this->replyTo ? $comments->firstWhere('id', $this->replyTo) : null,
            'requireLogin' => (bool) Settings::get('comments.require_login'),
            'askMobile' => Settings::get('comments.ask_mobile', 'optional'),
        ]);
    }
}
