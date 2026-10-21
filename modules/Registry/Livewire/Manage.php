<?php

/**
 * Khaneh Romantic — Account: build and share my list (details, items and quantities, who bought what)
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

namespace Modules\Registry\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\Registry\Models\Registry;
use Modules\Registry\Models\RegistryItem;

class Manage extends Component
{
    public string $title = '';

    public string $names = '';

    public ?string $event_date = null;

    public string $message = '';

    public bool $is_public = true;

    public bool $show_buyers = true;

    public bool $editing = false;

    public bool $saved = false;

    public function mount(): void
    {
        if ($registry = $this->registry()) {
            $this->fill($registry->only(['title', 'names', 'message', 'is_public', 'show_buyers']));
            $this->names = (string) $registry->names;
            $this->message = (string) $registry->message;
            $this->event_date = $registry->event_date?->toDateString();
        } else {
            $this->editing = true;
            $this->title = 'جهیزیه‌ی '.(Auth::guard('customer')->user()->first_name ?: 'ما');
        }
    }

    private function registry(): ?Registry
    {
        return Registry::query()->where('customer_id', Auth::guard('customer')->id())->latest('id')->first();
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'names' => ['nullable', 'string', 'max:120'],
            'event_date' => ['nullable', 'date'],
            'message' => ['nullable', 'string', 'max:600'],
            'is_public' => ['boolean'],
            'show_buyers' => ['boolean'],
        ]);
        $data['event_date'] = $data['event_date'] ?: null;

        $registry = $this->registry();
        $registry ? $registry->update($data) : Registry::query()->create($data + ['customer_id' => Auth::guard('customer')->id()]);
        $this->editing = false;
        $this->saved = true;
    }

    public function setQuantity(int $itemId, int $quantity): void
    {
        $this->item($itemId)?->update(['quantity' => max(1, min(99, $quantity))]);
    }

    public function remove(int $itemId): void
    {
        $this->item($itemId)?->delete();
    }

    private function item(int $id): ?RegistryItem
    {
        $registry = $this->registry();

        return $registry ? RegistryItem::query()->where('registry_id', $registry->id)->find($id) : null;
    }

    public function render()
    {
        $registry = $this->registry()?->load(['items.product.media', 'items.product.variants', 'items.claims']);

        return view('registry::livewire.manage', ['registry' => $registry]);
    }
}
