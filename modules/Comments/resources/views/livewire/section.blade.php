{{-- Comments under a post | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Jalali, PersianText};
    $avatar = fn (string $name) => mb_substr(trim($name) ?: '؟', 0, 1);
@endphp
<section id="comments" class="glass rounded-[var(--radius-glass)] p-6 sm:p-10" aria-labelledby="comments-title">
    <h2 id="comments-title" class="mb-6 text-xl font-black text-navy">
        نظرات @if ($total)<span class="text-base font-bold text-muted">({{ PersianText::toPersianDigits($total) }})</span>@endif
    </h2>

    <div class="space-y-1">
        @forelse ($comments as $comment)
            <article wire:key="c-{{ $comment->id }}" class="border-b border-line py-5 first:pt-0 last:border-0">
                <div class="flex gap-3">
                    <span @class(['grid size-10 shrink-0 place-items-center rounded-full text-sm font-black', 'bg-rose text-white' => $comment->is_staff, 'bg-cream text-caramel-600' => ! $comment->is_staff])>{{ $avatar($comment->name) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="font-bold text-navy">{{ $comment->is_staff ? 'خانه رمانتیک' : $comment->name }}</span>
                            <span class="text-xs text-subtle">{{ Jalali::format($comment->created_at) }}</span>
                        </p>
                        <p class="mt-2 text-sm leading-7 whitespace-pre-line text-muted">{{ $comment->body }}</p>
                        <div class="mt-2 flex items-center gap-4 text-xs">
                            <button type="button" wire:click="like({{ $comment->id }})" @disabled(in_array($comment->id, $liked, true))
                                    class="inline-flex items-center gap-1 text-subtle transition hover:text-rose disabled:text-rose">
                                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.65 16.9 9.64 16.9l-.02-.01a15 15 0 0 1-1.6-1A20 20 0 0 1 4.2 12.6C2.8 11 1.5 9 1.5 6.8A4.3 4.3 0 0 1 5.8 2.5c1.7 0 3.2 1 4.2 2.4 1-1.4 2.5-2.4 4.2-2.4a4.3 4.3 0 0 1 4.3 4.3c0 2.2-1.3 4.2-2.7 5.8a20 20 0 0 1-3.8 3.3 15 15 0 0 1-1.6 1l-.02.01h-.01a.75.75 0 0 1-.7 0Z"/></svg>
                                {{ $comment->likes ? PersianText::toPersianDigits($comment->likes) : 'پسندیدم' }}
                            </button>
                            @unless ($requireLogin && ! $customer)
                                <button type="button" wire:click="reply({{ $comment->id }})" class="font-bold text-navy/70 hover:text-rose"
                                        x-on:click="$nextTick(() => document.getElementById('comment-body')?.focus())">پاسخ</button>
                            @endunless
                        </div>

                        @foreach ($comment->replies as $reply)
                            <div wire:key="r-{{ $reply->id }}" @class(['mt-4 flex gap-3 rounded-2xl px-4 py-3', 'bg-cream' => $reply->is_staff, 'bg-surface/60 ring-1 ring-line' => ! $reply->is_staff])>
                                <span @class(['grid size-8 shrink-0 place-items-center rounded-full text-xs font-black', 'bg-rose text-white' => $reply->is_staff, 'bg-cream text-caramel-600' => ! $reply->is_staff])>{{ $avatar($reply->name) }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2 text-xs">
                                        <span @class(['font-bold', 'text-rose' => $reply->is_staff, 'text-navy' => ! $reply->is_staff])>{{ $reply->is_staff ? 'پاسخ خانه رمانتیک' : $reply->name }}</span>
                                        <span class="text-subtle">{{ Jalali::format($reply->created_at) }}</span>
                                    </p>
                                    <p class="mt-1.5 text-sm leading-7 whitespace-pre-line text-navy">{{ $reply->body }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>
        @empty
            <div class="py-6 text-center">
                <p class="font-bold text-navy">هنوز نظری ثبت نشده است.</p>
                <p class="mt-1 text-sm text-muted">اولین نفری باشید که نظرش را می‌نویسد.</p>
            </div>
        @endforelse
        @if ($topLevel > $comments->count())
            <button type="button" wire:click="loadMore" class="btn-glass mt-2">نظرهای بیشتر</button>
        @endif
    </div>

    <div class="mt-8 border-t border-line pt-8">
        @if ($requireLogin && ! $customer)
            <div class="rounded-[var(--radius-card)] bg-surface/60 p-5 text-center ring-1 ring-line">
                <p class="font-bold text-navy">برای نوشتن نظر، اول وارد شوید.</p>
                <a href="{{ route('customer.login', ['back' => request()->getPathInfo().'#comments']) }}" class="btn-navy mt-4" data-no-prerender>ورود</a>
            </div>
        @else
            @if ($notice)
                <p class="mb-4 rounded-2xl bg-rose-100 px-4 py-3 text-sm font-bold text-rose" role="status">{{ $notice }}</p>
            @endif
            <form wire:submit="submit" class="grid gap-4 sm:grid-cols-2" novalidate>
                <p class="font-black text-navy sm:col-span-2">
                    @if ($replyingTo)
                        پاسخ به {{ $replyingTo->is_staff ? 'خانه رمانتیک' : $replyingTo->name }}
                        <button type="button" wire:click="cancelReply" class="ms-2 text-xs font-bold text-rose">انصراف</button>
                    @else
                        نظر شما
                    @endif
                </p>
                <div>
                    <label for="comment-name" class="mb-1.5 block text-sm font-bold text-navy">نام</label>
                    <input id="comment-name" type="text" wire:model="name" autocomplete="name" maxlength="60" class="field">
                    @error('name')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
                </div>
                @if ($askMobile !== 'hidden' && ! $customer)
                    <div>
                        <label for="comment-mobile" class="mb-1.5 block text-sm font-bold text-navy">موبایل @if ($askMobile !== 'required')<span class="font-normal text-subtle">(اختیاری، نمایش داده نمی‌شود)</span>@endif</label>
                        <input id="comment-mobile" type="tel" inputmode="numeric" dir="ltr" wire:model="mobile" placeholder="09xxxxxxxxx" class="field text-start">
                        @error('mobile')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
                    </div>
                @endif
                <div class="sm:col-span-2">
                    <label for="comment-body" class="mb-1.5 block text-sm font-bold text-navy">متن</label>
                    <textarea id="comment-body" rows="4" wire:model="body" maxlength="3000" class="field" placeholder="نظر یا سؤال خود را بنویسید…"></textarea>
                    @error('body')<p class="mt-1 text-xs text-rose">{{ $message }}</p>@enderror
                </div>
                <div class="hidden" aria-hidden="true"><input type="text" wire:model="website" tabindex="-1" autocomplete="off"></div>
                <div class="sm:col-span-2">
                    <button type="submit" class="btn-rose" wire:loading.attr="disabled">ثبت نظر</button>
                </div>
            </form>
        @endif
    </div>
</section>
