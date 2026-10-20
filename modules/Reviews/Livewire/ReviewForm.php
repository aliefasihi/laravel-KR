<?php

/**
 * Khaneh Romantic — «نظر شما درباره‌ی این محصول» (customers, and guests when allowed)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Reviews\Livewire;

use App\Core\Livewire\StopsBots;
use App\Core\Settings\Settings;
use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Catalog\Models\Product;
use Modules\Crm\Support\Forms;
use Modules\Customer\Support\Mobile;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Support\Buyers;

class ReviewForm extends Component
{
    use StopsBots;

    #[Locked]
    public int $productId;

    public int $rating = 0;

    public string $title = '';

    public string $body = '';

    public string $name = '';       // guests

    public string $mobile = '';     // guests

    public bool $sent = false;

    /** «log in to review» link back to this product's reviews (built once, not on every render) */
    #[Locked]
    public string $loginUrl = '';

    public function mount(int $productId, ?string $productUrl = null): void
    {
        $this->productId = $productId;
        $path = parse_url($productUrl ?? Product::query()->find($productId)?->url() ?? '/', PHP_URL_PATH) ?: '/';
        $this->loginUrl = route('customer.login', ['back' => $path.'#reviews']);
    }

    private function guestsAllowed(): bool
    {
        return (bool) Settings::get('reviews.allow_guests') && ! Settings::get('reviews.require_purchase');
    }

    public function submit(): void
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer || $this->guestsAllowed(), 403);

        $this->mobile = PersianText::toLatinDigits($this->mobile);
        $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:80'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
            'name' => [$customer ? 'nullable' : 'required', 'string', 'max:60'],
            'mobile' => [$customer ? 'nullable' : 'required', fn ($a, $v, $fail) => $customer || Mobile::normalize($v) ? null : $fail('شماره‌ی موبایل درست نیست.')],
        ], ['rating.between' => 'امتیاز را با ستاره‌ها انتخاب کنید.'], ['body' => 'متن نظر', 'title' => 'عنوان', 'name' => 'نام', 'mobile' => 'موبایل']);

        if ($this->looksLikeBot(minSeconds: 0)) {
            $this->sent = true;

            return;
        }
        $key = 'review:'.($customer ? 'c'.$customer->id : request()->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('body', 'کمی بعد دوباره امتحان کنید.');

            return;
        }
        RateLimiter::hit($key, 3600);

        $verified = Buyers::bought($customer?->id, $this->productId);
        if (Settings::get('reviews.require_purchase') && ! $verified) {
            $this->addError('body', 'فقط خریداران این محصول می‌توانند نظر بنویسند.');

            return;
        }

        $approve = match (Settings::get('reviews.auto_approve')) {
            'all' => true,
            'verified' => $verified,
            default => false,
        };
        $mobile = $customer?->mobile ?? Mobile::normalize($this->mobile);
        $name = $customer ? ($customer->fullName() ?: 'مشتری خانه رمانتیک') : trim($this->name);

        $review = Review::query()->updateOrCreate(
            $customer ? ['product_id' => $this->productId, 'customer_id' => $customer->id]
                : ['product_id' => $this->productId, 'customer_id' => null, 'mobile' => $mobile],
            [
                'name' => $name,
                'mobile' => $mobile,
                'rating' => $this->rating,
                'title' => trim($this->title) ?: null,
                'body' => trim($this->body),
                'is_verified_buyer' => $verified,
                'status' => $approve ? 'approved' : 'pending',
                'ip' => request()->ip(),
            ],
        );

        Forms::record('product_review', array_filter([
            'name' => $name, 'mobile' => $mobile, 'product' => Product::query()->whereKey($this->productId)->value('name'),
            'rating' => $this->rating, 'title' => $review->title, 'body' => $review->body, 'verified' => $verified ? 'بله' : null,
        ]), ['name' => $name, 'mobile' => $mobile, 'customer_id' => $customer?->id], $review, status: 'read');

        $this->sent = true;
        $this->reset(['rating', 'title', 'body']);
        $this->dispatch('toast', message: $approve ? 'نظر شما منتشر شد. ممنون!' : 'ممنون! نظر شما پس از بررسی منتشر می‌شود.');
    }

    public function render()
    {
        $customer = Auth::guard('customer')->user();

        return view('reviews::livewire.review-form', [
            'customer' => $customer,
            'guests' => $this->guestsAllowed(),
            'existing' => $customer ? Review::query()->where('product_id', $this->productId)->where('customer_id', $customer->id)->first() : null,
        ]);
    }
}
