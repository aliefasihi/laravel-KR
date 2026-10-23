{{-- Hook «account.order.actions»: «خبر وضعیت سفارش با نوتیفیکیشن» (shown by push.js where the browser can) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@if (! in_array($order->status, ['completed', 'cancelled', 'refunded', 'failed'], true))
    <button type="button" class="btn-glass hidden" data-push-button data-push-subscribe data-push-done="نوتیفیکیشن سفارش روشن است ✓">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
        <span>خبر ارسال سفارش با نوتیفیکیشن</span>
    </button>
    <p class="hidden w-full text-xs leading-6 text-muted" data-push-ios>برای گرفتن نوتیفیکیشن در آیفون، اول سایت را با «Add to Home Screen» به صفحه‌ی اصلی اضافه کنید و از همان‌جا باز کنید.</p>
@endif
