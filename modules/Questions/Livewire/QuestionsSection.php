<?php

/**
 * Khaneh Romantic — «پرسش و پاسخ» under a product: answered questions + «سؤال بپرسید»
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Questions\Livewire;

use App\Core\Livewire\StopsBots;
use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Catalog\Models\Product;
use Modules\Crm\Support\Forms;
use Modules\Customer\Support\Mobile;
use Modules\Questions\Models\Question;

class QuestionsSection extends Component
{
    use StopsBots;

    #[Locked]
    public int $productId;

    public string $body = '';

    public string $name = '';

    public string $mobile = '';

    public bool $notify = true;

    public bool $sent = false;

    public int $shown = 5;

    #[Locked]
    public string $loginUrl = '';

    public function mount(int $productId, ?string $productUrl = null): void
    {
        $this->productId = $productId;
        $path = parse_url($productUrl ?? Product::query()->find($productId)?->url() ?? '/', PHP_URL_PATH) ?: '/';
        $this->loginUrl = route('customer.login', ['back' => $path.'#questions']);
    }

    public function ask(): void
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer || Settings::get('questions.allow_guests', true), 403);

        $this->mobile = PersianText::toLatinDigits($this->mobile);
        $this->validate([
            'body' => ['required', 'string', 'min:8', 'max:1000'],
            'name' => [$customer ? 'nullable' : 'required', 'string', 'max:60'],
            'mobile' => ['nullable', fn ($a, $v, $fail) => $v === '' || Mobile::normalize($v) ? null : $fail('شماره‌ی موبایل درست نیست.')],
        ], [], ['body' => 'سؤال', 'name' => 'نام', 'mobile' => 'موبایل']);

        if ($this->looksLikeBot(minSeconds: 0)) {
            $this->sent = true;

            return;
        }
        $key = 'question:'.($customer ? 'c'.$customer->id : request()->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('body', 'کمی بعد دوباره امتحان کنید.');

            return;
        }
        RateLimiter::hit($key, 3600);

        $mobile = $customer?->mobile ?? Mobile::normalize($this->mobile);
        $name = $customer ? ($customer->fullName() ?: 'مشتری '.config('khaneh.store.name')) : trim($this->name);
        $question = Question::query()->create([
            'product_id' => $this->productId, 'customer_id' => $customer?->id, 'name' => $name, 'mobile' => $mobile,
            'body' => trim($this->body), 'notify' => $this->notify && $mobile, 'ip' => request()->ip(),
        ]);

        if (Modules::enabled('Crm')) {
            Forms::record('product_question', array_filter([
                'name' => $name, 'mobile' => $mobile, 'product' => Product::query()->whereKey($this->productId)->value('name'), 'body' => $question->body,
            ]), ['name' => $name, 'mobile' => $mobile, 'customer_id' => $customer?->id], $question, status: 'read');
        }

        $this->sent = true;
        $this->reset(['body']);
        $this->dispatch('toast', message: $mobile && $this->notify ? 'سؤال شما ثبت شد؛ جواب را پیامک می‌کنیم.' : 'سؤال شما ثبت شد؛ جواب همین‌جا منتشر می‌شود.');
    }

    public function more(): void
    {
        $this->shown += 10;
    }

    public function helpful(int $id): void
    {
        $voted = session('questions.helpful', []);
        if (in_array($id, $voted, true)) {
            return;
        }
        Question::query()->published()->whereKey($id)->where('product_id', $this->productId)->increment('helpful');
        session(['questions.helpful' => [...$voted, $id]]);
    }

    public function render()
    {
        $base = Question::query()->published()->where('product_id', $this->productId);

        return view('qa::livewire.section', [
            'questions' => (clone $base)->orderByDesc('helpful')->latest('answered_at')->limit($this->shown)->get(),
            'total' => (clone $base)->count(),
            'customer' => Auth::guard('customer')->user(),
            'guests' => (bool) Settings::get('questions.allow_guests', true),
            'voted' => session('questions.helpful', []),
        ]);
    }
}
