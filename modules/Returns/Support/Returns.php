<?php

/**
 * Khaneh Romantic — The return flow
 * نویسنده: علی فصیحی (Ali Fasihi)
 *
 *   customer asks (items + quantities, reason, photos, card/wallet) → requested
 *   staff approve (with instructions: where to send it) or reject (with the reason)
 *   the parcel arrives → received
 *   staff refund (amount suggested from the returned lines) → refunded: OrderWorkflow::refund puts
 *   the quantities back in stock and, for «کیف پول», the Loyalty module credits the wallet
 * The customer gets an SMS at each step (templates in تنظیمات ← مرجوعی; values RETURN, ORDER, AMOUNT, NOTE).
 * Returns are possible for shipped/completed orders within the window (7 days by default) after
 * delivery; a line can't be in two open requests, nor returned beyond what was bought.
 */

namespace Modules\Returns\Support;

use App\Core\Modules\Modules;
use App\Core\Settings\Settings;
use App\Core\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Order\Models\Order;
use Modules\Order\Support\OrderWorkflow;
use Modules\Returns\Models\ReturnItem;
use Modules\Returns\Models\ReturnRequest;
use Modules\Sms\Support\Sms;
use Throwable;

final class Returns
{
    public function __construct(private OrderWorkflow $workflow) {}

    public static function windowDays(): int
    {
        return max(1, (int) Settings::get('returns.window_days', 7));
    }

    /** The day returns close for this order (from delivery, or shipping if never marked delivered). */
    public static function deadline(Order $order): ?Carbon
    {
        $from = $order->completed_at ?? $order->shipped_at;

        return $from?->copy()->addDays(self::windowDays())->endOfDay();
    }

    /** @return array<int, int> order item id => quantity that can still be returned */
    public function returnable(Order $order): array
    {
        if (! in_array($order->status, ['shipped', 'completed'], true) || ! ($deadline = self::deadline($order)) || $deadline->isPast()) {
            return [];
        }
        $open = ReturnItem::query()->whereHas('request', fn ($q) => $q->where('order_id', $order->id)->whereIn('status', ReturnRequest::OPEN))
            ->selectRaw('order_item_id, sum(quantity) as q')->groupBy('order_item_id')->pluck('q', 'order_item_id');

        $left = [];
        foreach ($order->items as $item) {
            $qty = $item->quantity - $item->refunded_quantity - (int) ($open[$item->id] ?? 0);
            if ($qty > 0) {
                $left[$item->id] = $qty;
            }
        }

        return $left;
    }

    /**
     * @param  array<int, int>  $quantities  order item id => quantity
     * @param  list<UploadedFile>  $photos
     */
    public function request(Order $order, array $quantities, string $reason, ?string $description, string $method, ?string $iban, array $photos = []): ReturnRequest
    {
        $left = $this->returnable($order);
        $lines = [];
        foreach ($quantities as $itemId => $qty) {
            $qty = min((int) $qty, $left[(int) $itemId] ?? 0);
            if ($qty > 0) {
                $lines[(int) $itemId] = $qty;
            }
        }
        if ($lines === []) {
            throw ValidationException::withMessages(['quantities' => 'حداقل یک کالا برای مرجوعی انتخاب کنید.']);
        }

        return DB::transaction(function () use ($order, $lines, $reason, $description, $method, $iban, $photos) {
            $n = ReturnRequest::query()->where('order_id', $order->id)->count() + 1;
            $request = ReturnRequest::query()->create([
                'number' => 'R-'.$order->number.'-'.$n,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'reason' => array_key_exists($reason, ReturnRequest::REASONS) ? $reason : 'other',
                'description' => $description ? trim($description) : null,
                'refund_method' => $method === 'wallet' && Modules::enabled('Loyalty') ? 'wallet' : 'card',
                'iban' => $method === 'wallet' ? null : $iban,
                'photos' => array_values(array_map(fn (UploadedFile $f) => $f->store('returns/'.now()->format('Y/m'), 'local'), $photos)),
            ]);
            foreach ($lines as $itemId => $qty) {
                $request->items()->create(['order_item_id' => $itemId, 'quantity' => $qty]);
            }
            $order->note('درخواست مرجوعی '.$request->number.' ثبت شد ('.$request->reasonLabel().').', 'system');

            return $request;
        });
    }

    public function approve(ReturnRequest $request, ?string $instructions, ?int $userId = null): void
    {
        $request->update(['status' => 'approved', 'admin_note' => $instructions ?: Settings::get('returns.instructions'), 'approved_at' => now(), 'handled_by' => $userId]);
        $request->order?->note('مرجوعی '.$request->number.' تأیید شد.', 'system', $userId);
        $this->notify($request, 'approved');
    }

    public function reject(ReturnRequest $request, string $reason, ?int $userId = null): void
    {
        $request->update(['status' => 'rejected', 'admin_note' => $reason, 'handled_by' => $userId]);
        $request->order?->note('مرجوعی '.$request->number.' رد شد: '.$reason, 'system', $userId);
        $this->notify($request, 'rejected');
    }

    public function receive(ReturnRequest $request, ?int $userId = null): void
    {
        $request->update(['status' => 'received', 'received_at' => now(), 'handled_by' => $userId]);
        $request->order?->note('کالای مرجوعی '.$request->number.' رسید.', 'system', $userId);
    }

    /** Money back for the returned lines; stock goes back; wallet refunds are credited by the Loyalty module. */
    public function refund(ReturnRequest $request, int $amount, ?int $userId = null): void
    {
        $order = $request->order->load('items');
        $quantities = $request->items->mapWithKeys(fn (ReturnItem $i) => [$i->order_item_id => $i->quantity])->all();
        $this->workflow->refund($order, $amount, $quantities, 'مرجوعی '.$request->number, $userId, toWallet: $request->refund_method === 'wallet');

        $request->update(['status' => 'refunded', 'refund_amount' => $amount, 'refunded_at' => now(), 'handled_by' => $userId]);
        $this->notify($request, 'refunded');
    }

    public function cancel(ReturnRequest $request): void
    {
        if ($request->status === 'requested') {
            $request->update(['status' => 'cancelled']);
        }
    }

    /** values: RETURN, ORDER, AMOUNT (toman), NOTE — Kavenegar: token, token2, token3, token10 */
    private function notify(ReturnRequest $request, string $step): void
    {
        $template = Settings::get('returns.'.$step.'_template');
        $mobile = $request->customer?->mobile ?? $request->order?->customer_mobile;
        if (! $template || ! $mobile || ! Modules::enabled('Sms')) {
            return;
        }
        try {
            app(Sms::class)->template($mobile, (string) $template, [
                $request->number,
                (string) $request->order?->number,
                $request->refund_amount ? Money::toman($request->refund_amount) : '-',
                mb_substr((string) ($request->admin_note ?: '-'), 0, 80),
            ]);
        } catch (Throwable $e) {
            Log::warning('Return SMS failed: '.$e->getMessage());
        }
    }
}
