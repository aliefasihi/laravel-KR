<?php

/**
 * Khaneh Romantic — The shared list page: guests buy an item here or mark it «I'll get this»
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Registry\Livewire;

use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Customer\Support\Mobile;
use Modules\Registry\Models\Registry;
use Modules\Registry\Models\RegistryClaim;
use Modules\Registry\Models\RegistryItem;
use Modules\Registry\Support\RegistryPurchases;

class PublicList extends Component
{
    #[Locked]
    public int $registryId;

    /** item being reserved (the small form is open for it) */
    public ?int $reserving = null;

    public string $name = '';

    public string $mobile = '';

    public ?string $notice = null;

    public function mount(Registry $registry): void
    {
        $this->registryId = $registry->id;
    }

    private function item(int $id): ?RegistryItem
    {
        return RegistryItem::query()->where('registry_id', $this->registryId)->with(['claims', 'product.variants'])->find($id);
    }

    public function buy(int $itemId)
    {
        $item = $this->item($itemId);
        if (! $item || $item->remaining() < 1) {
            $this->notice = 'این قلم دیگر لازم نیست.';

            return null;
        }
        if (app(RegistryPurchases::class)->addToCart($item) < 1) {
            $this->notice = 'این کالا فعلاً موجود نیست؛ می‌توانید «رزرو می‌کنم» را بزنید.';

            return null;
        }

        return $this->redirect(route('checkout.cart'));
    }

    public function reserve(): void
    {
        $data = $this->validate(['name' => ['required', 'string', 'max:60'], 'mobile' => ['nullable', 'string', 'max:20']], [], ['name' => 'نام']);
        $item = $this->reserving ? $this->item($this->reserving) : null;
        if (! $item || $item->remaining() < 1) {
            $this->reserving = null;

            return;
        }
        if (RateLimiter::tooManyAttempts('registry-claim:'.request()->ip(), 15)) {
            $this->notice = 'لطفاً کمی بعد دوباره امتحان کنید.';

            return;
        }
        RateLimiter::hit('registry-claim:'.request()->ip(), 3600);

        RegistryClaim::query()->create([
            'registry_item_id' => $item->id,
            'kind' => 'reserved',
            'name' => trim($data['name']),
            'mobile' => Mobile::normalize($data['mobile'] ?? null),
            'quantity' => 1,
        ]);
        $this->reserving = null;
        $this->notice = 'ممنون! «'.$item->product->name.'» به نام شما رزرو شد.';
    }

    public function render()
    {
        $registry = Registry::query()->with(['items.product.media', 'items.product.variants', 'items.product.brand', 'items.claims'])->findOrFail($this->registryId);

        return view('registry::livewire.public-list', ['registry' => $registry]);
    }
}
