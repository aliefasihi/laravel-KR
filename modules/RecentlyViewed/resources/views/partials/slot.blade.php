{{-- Placeholder the browser fills with the visitor's recently viewed products; $current = this product (recorded) | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div x-data="recentlyViewed({{ (int) ($current ?? 0) }}, @js(route('recent.rail')))" x-html="html" @class(['empty:hidden', $class ?? ''])></div>
