{{-- Glass pagination with Persian digits | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php use App\Core\Support\PersianText; @endphp
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="صفحه‌بندی" class="flex justify-center">
        <ul class="glass flex items-center gap-1 rounded-full p-1.5">
            @if ($paginator->onFirstPage())
                <li><span class="grid size-10 place-items-center rounded-full text-subtle/50" aria-disabled="true" aria-label="صفحه قبل">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg>
                </span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid size-10 place-items-center rounded-full text-navy hover:bg-white" aria-label="صفحه قبل">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M7.2 4.2a.75.75 0 0 0 0 1.06L11.94 10 7.2 14.74a.75.75 0 1 0 1.06 1.06l5.27-5.27a.75.75 0 0 0 0-1.06L8.26 4.2a.75.75 0 0 0-1.06 0Z"/></svg>
                </a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="grid size-10 place-items-center text-subtle">…</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span aria-current="page" class="grid size-10 place-items-center rounded-full bg-navy text-sm font-black text-white shadow">{{ PersianText::toPersianDigits($page) }}</span></li>
                        @else
                            <li><a href="{{ $url }}" class="grid size-10 place-items-center rounded-full text-sm font-bold text-navy hover:bg-white" aria-label="صفحه {{ $page }}">{{ PersianText::toPersianDigits($page) }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid size-10 place-items-center rounded-full text-navy hover:bg-white" aria-label="صفحه بعد">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                </a></li>
            @else
                <li><span class="grid size-10 place-items-center rounded-full text-subtle/50" aria-disabled="true" aria-label="صفحه بعد">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"/></svg>
                </span></li>
            @endif
        </ul>
    </nav>
@endif
