{{-- Content: journal index — «بازار»: a list of row cards beside a side column (product search, latest titles,
     a shop box) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@extends('layouts.app')

@section('title', 'مجله '.config('khaneh.store.name'))
@section('meta_description', 'راهنمای خرید، نگهداری و ایده‌های چیدمان لوازم آشپزخانه، سرو و پذیرایی و دکوراسیون خانه.')
@section('canonical', route('content.blog').($posts->currentPage() > 1 ? '?page='.$posts->currentPage() : ''))

@section('content')
    <div class="mx-auto max-w-[88rem] space-y-4 px-3 pt-4 sm:px-5">
        <x-breadcrumbs :items="['مجله' => null]" class="[&_ol]:!border-0 [&_ol]:!bg-transparent [&_ol]:!px-0 [&_ol]:!shadow-none" />

        <div class="grid gap-4 lg:grid-cols-[1fr_20rem] [&>*]:min-w-0">
            <section class="space-y-3" aria-labelledby="blog-title">
                <div class="rounded-2xl border border-line bg-surface p-4 sm:p-5">
                    <h1 id="blog-title" class="text-xl font-black text-navy sm:text-2xl">مجله {{ config('khaneh.store.name') }}</h1>
                    <p class="mt-1 text-sm text-muted">راهنمای خرید، چیدمان و نگهداری لوازم خانه و آشپزخانه</p>
                </div>
                @forelse ($posts as $post)
                    <x-post.card :post="$post" />
                @empty
                    <p class="rounded-2xl border border-line bg-surface py-16 text-center text-muted">هنوز مطلبی منتشر نشده است.</p>
                @endforelse
                {{ $posts->links() }}
            </section>

            <aside class="space-y-4 lg:sticky lg:top-36 lg:self-start">
                <div class="rounded-2xl border border-line bg-surface p-4">
                    <p class="mb-3 text-sm font-black text-navy">جستجو در کالاها</p>
                    <x-search-box />
                </div>
                @if ($posts->isNotEmpty())
                    <div class="rounded-2xl border border-line bg-surface p-4">
                        <p class="mb-2 text-sm font-black text-navy">تازه‌ترین مطالب</p>
                        <ol class="divide-y divide-line text-[13px]">
                            @foreach ($posts->take(5) as $post)
                                <li><a href="{{ $post->url() }}" class="flex gap-3 py-2.5 text-navy hover:text-deep"><span class="font-black text-deep">{{ \App\Core\Support\PersianText::toPersianDigits($loop->iteration) }}</span>{{ $post->title }}</a></li>
                            @endforeach
                        </ol>
                    </div>
                @endif
                <a href="{{ route('catalog.shop', ['sale' => 1]) }}" class="block rounded-2xl bg-deep p-5 text-white">
                    <span class="block text-lg font-black">شگفت‌انگیزهای امروز</span>
                    <span class="mt-1 block text-sm text-white/80">تخفیف‌های محدود فروشگاه را ببینید ←</span>
                </a>
            </aside>
        </div>
    </div>
@endsection
