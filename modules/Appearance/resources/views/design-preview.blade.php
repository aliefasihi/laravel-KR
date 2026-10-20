{{-- Admin is previewing a demo (only they see it) | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div class="fixed inset-x-0 bottom-20 z-[70] flex justify-center px-3 lg:bottom-4" role="status">
    <div class="flex flex-wrap items-center gap-3 rounded-full bg-deep px-5 py-2.5 text-sm text-white shadow-[0_18px_40px_-16px_rgb(35_40_79/0.8)]">
        <span>پیش‌نمایش <b class="text-caramel">{{ $demo['label'] }}</b> — فقط شما می‌بینید</span>
        <a href="{{ url('/admin/settings') }}" class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold hover:bg-white/25" data-no-prerender>انتخاب در تنظیمات</a>
        <a href="{{ request()->fullUrlWithQuery(['design' => 'off']) }}" class="rounded-full bg-rose px-3 py-1 text-xs font-bold hover:bg-rose/90" data-no-prerender>خروج</a>
    </div>
</div>
