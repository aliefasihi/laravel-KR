<?php

/**
 * Khaneh Romantic — «درخواست مرجوعی» for one order (customer account)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Returns\Livewire;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use App\Core\Support\PersianText;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Order\Models\Order;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\Support\Returns;

class ReturnForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $orderId;

    /** @var array<int|string, int|string> order item id => quantity */
    public array $quantities = [];

    public string $reason = '';

    public string $description = '';

    public string $method = 'card';

    public string $iban = '';

    /** @var array<int, mixed> */
    public array $photos = [];

    public function mount(Order $order): void
    {
        abort_unless($order->customer_id && $order->customer_id === Auth::guard('customer')->id(), 404);
        $this->orderId = $order->id;
    }

    private function order(): Order
    {
        return Order::query()->with('items.product.media')->findOrFail($this->orderId);
    }

    public function submit(Returns $returns)
    {
        $order = $this->order();
        $this->iban = strtoupper(preg_replace('/\s+/', '', PersianText::toLatinDigits($this->iban)));
        if ($this->iban !== '' && ! str_starts_with($this->iban, 'IR')) {
            $this->iban = 'IR'.$this->iban;
        }

        $this->validate([
            'reason' => ['required', 'in:'.implode(',', array_keys(ReturnRequest::REASONS))],
            'description' => [$this->reason === 'other' ? 'required' : 'nullable', 'string', 'max:1000'],
            'method' => ['required', 'in:card,wallet'],
            'iban' => [$this->method === 'card' ? 'required' : 'nullable', 'regex:/^IR\d{24}$/'],
            'photos' => [in_array($this->reason, ReturnRequest::PHOTO_REASONS, true) && Settings::get('returns.photo_required', true) ? 'required' : 'nullable', 'array', 'max:3'],
            'photos.*' => ['image', 'max:6144'],
        ], [
            'iban.regex' => 'شماره‌ی شبا ۲۴ رقم بعد از IR است.',
            'photos.required' => 'برای این دلیل، یک عکس از کالا لازم است.',
        ], ['reason' => 'دلیل', 'description' => 'توضیح', 'iban' => 'شماره‌ی شبا', 'photos' => 'عکس']);

        $request = $returns->request($order, array_filter(array_map('intval', $this->quantities)), $this->reason, $this->description, $this->method, $this->iban ?: null, $this->photos);

        session()->flash('status', 'درخواست مرجوعی '.$request->number.' ثبت شد. نتیجه‌ی بررسی را پیامک می‌کنیم.');

        return $this->redirectRoute('customer.returns', navigate: false);
    }

    public function render(Returns $returns)
    {
        $order = $this->order();

        return view('returns::livewire.form', [
            'order' => $order,
            'left' => $returns->returnable($order),
            'deadline' => Returns::deadline($order),
            'wallet' => Modules::enabled('Loyalty'),
            'policy' => Settings::get('returns.policy'),
        ]);
    }
}
