<?php

/**
 * Khaneh Romantic — «نظر شما درباره‌ی این محصول»
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Reviews\Livewire;

use App\Core\Settings\Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Support\Buyers;

class ReviewForm extends Component
{
    #[Locked]
    public int $productId;

    public int $rating = 0;

    public string $title = '';

    public string $body = '';

    public bool $sent = false;

    public function mount(int $productId): void
    {
        $this->productId = $productId;
    }

    public function submit(): void
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer, 403);

        $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:80'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ], ['rating.between' => 'امتیاز را با ستاره‌ها انتخاب کنید.'], ['body' => 'متن نظر', 'title' => 'عنوان']);

        $key = 'review:'.$customer->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('body', 'کمی بعد دوباره امتحان کنید.');

            return;
        }
        RateLimiter::hit($key, 3600);

        $verified = Buyers::bought($customer->id, $this->productId);
        if (Settings::get('reviews.require_purchase') && ! $verified) {
            $this->addError('body', 'فقط خریداران این محصول می‌توانند نظر بنویسند.');

            return;
        }

        $approve = match (Settings::get('reviews.auto_approve')) {
            'all' => true,
            'verified' => $verified,
            default => false,
        };

        Review::query()->updateOrCreate(
            ['product_id' => $this->productId, 'customer_id' => $customer->id],
            [
                'name' => $customer->fullName() ?: 'مشتری خانه رمانتیک',
                'rating' => $this->rating,
                'title' => trim($this->title) ?: null,
                'body' => trim($this->body),
                'is_verified_buyer' => $verified,
                'status' => $approve ? 'approved' : 'pending',
                'ip' => request()->ip(),
            ],
        );

        $this->sent = true;
        $this->reset(['rating', 'title', 'body']);
        $this->dispatch('toast', message: $approve ? 'نظر شما منتشر شد. ممنون!' : 'ممنون! نظر شما پس از بررسی منتشر می‌شود.');
    }

    public function render()
    {
        $customer = Auth::guard('customer')->user();

        return view('reviews::livewire.review-form', [
            'customer' => $customer,
            'existing' => $customer ? Review::query()->where('product_id', $this->productId)->where('customer_id', $customer->id)->first() : null,
        ]);
    }
}
