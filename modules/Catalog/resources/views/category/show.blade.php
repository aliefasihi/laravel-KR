{{-- Catalog: category page | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@php
    use App\Core\Support\PersianText;
    $page = $products->currentPage();
    $canonical = $category->url().($page > 1 ? '?page='.$page : '');
@endphp

@section('title', ($category->meta_title ?: $category->name).($page > 1 ? ' — صفحه '.PersianText::toPersianDigits($page) : ''))
@section('meta_description', $category->meta_description)
@section('canonical', $filtered ? $category->url() : $canonical)
@if ($filtered)
    @section('robots', 'noindex, follow')
@endif

@section('content')
    <div class="mx-auto max-w-7xl px-4 pt-6">
        <x-breadcrumbs :items="array_slice($category->trail(), 0, -1, true) + [$category->name => null]" />

        <x-placement key="category.header" :context-id="$category->id" class="mt-6" />

        <header class="mt-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-4xl leading-tight sm:text-5xl">{{ $category->name }}</h1>
                <p class="mt-2 text-sm text-ink-soft">{{ PersianText::toPersianDigits($products->total()) }} محصول</p>
            </div>
        </header>

        @if ($category->children->isNotEmpty())
            <nav class="mt-6 -mx-4 overflow-x-auto px-4" aria-label="زیردسته‌ها">
                <ul class="flex gap-2 whitespace-nowrap">
                    @foreach ($category->children as $child)
                        <li><a href="{{ $child->url() }}" class="inline-block rounded-full border border-line bg-white px-4 py-2 text-sm hover:border-glaze hover:text-glaze">{{ $child->name }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <div class="mt-8 grid gap-8 lg:grid-cols-[15rem_1fr]">
            {{-- Filters: plain GET form, works without JS, auto-submits with it --}}
            <aside>
                <form method="get" action="{{ $category->url() }}" x-data="autoSubmit" @change="submit" class="space-y-6 lg:sticky lg:top-36">
                    <div>
                        <label for="sort" class="mb-2 block text-sm font-bold">مرتب‌سازی</label>
                        <select id="sort" name="sort" class="w-full rounded-xl border border-line bg-white px-3 py-2 text-sm">
                            @foreach ($sorts as $key => $label)
                                <option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($brands->count() > 1)
                        <fieldset>
                            <legend class="mb-2 text-sm font-bold">برند</legend>
                            <div class="space-y-2">
                                @foreach ($brands as $brand)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="brand[]" value="{{ $brand->slug }}" @checked(in_array($brand->slug, $filters['brand'], true)) class="size-4 rounded accent-glaze">
                                        {{ $brand->name_fa }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock']) class="size-4 rounded accent-glaze">
                        فقط کالاهای موجود
                    </label>

                    <noscript><button type="submit" class="w-full rounded-xl bg-glaze py-2 text-sm font-bold text-white">اعمال فیلتر</button></noscript>

                    @if ($filtered)
                        <a href="{{ $category->url() }}" class="block text-sm text-glaze">حذف فیلترها</a>
                    @endif
                </form>
            </aside>

            <section aria-label="محصولات {{ $category->name }}">
                @if ($products->isEmpty())
                    <div class="rounded-[var(--radius-tile)] border border-dashed border-line bg-white px-6 py-16 text-center text-ink-soft">
                        <p>با این فیلترها محصولی پیدا نشد.</p>
                        <a href="{{ $category->url() }}" class="mt-3 inline-block font-medium text-glaze">نمایش همه محصولات {{ $category->name }}</a>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-x-4 gap-y-8 md:grid-cols-3 xl:grid-cols-4">
                        @foreach ($products as $product)
                            <x-product.card :product="$product" :eager="$loop->index < 4" />
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $products->links() }}</div>
                @endif
            </section>
        </div>

        @if ($category->description && $page === 1)
            <section class="prose-fa mx-auto mt-16 max-w-3xl border-t border-line pt-10">
                {!! $category->description !!}
            </section>
        @endif
    </div>
@endsection
