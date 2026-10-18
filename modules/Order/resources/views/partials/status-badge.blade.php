{{-- Order status pill | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    $tone = [
        'pending' => 'bg-caramel-100 text-caramel-600', 'on_hold' => 'bg-navy/10 text-navy', 'processing' => 'bg-rose-100 text-rose',
        'shipped' => 'bg-navy/10 text-navy', 'completed' => 'bg-ok/15 text-ok', 'cancelled' => 'bg-navy/5 text-muted',
        'refunded' => 'bg-navy/5 text-muted', 'failed' => 'bg-rose-100 text-rose',
    ][$order->status] ?? 'bg-navy/5 text-muted';
@endphp
<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold {{ $tone }}">
    <span class="size-1.5 rounded-full bg-current"></span>{{ $order->statusLabel() }}
</span>
