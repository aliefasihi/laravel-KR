{{-- Inbox: one submission in the modal | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php
    use App\Core\Support\{Jalali, PersianText};
    use Modules\Crm\Filament\Resources\Contacts\ContactResource;
    $fields = $definition?->fields ?? [];
    $data = $submission->data ?? [];
    $ordered = collect($fields)->keys()->merge(array_keys($data))->unique()->filter(fn ($k) => array_key_exists($k, $data));
    $contact = $submission->contact;
@endphp
<div class="grid gap-4 text-sm">
    <dl class="grid gap-3 rounded-xl bg-gray-50 p-4 dark:bg-white/5">
        @foreach ($ordered as $key)
            @php($value = $data[$key])
            <div class="grid gap-1 sm:grid-cols-[9rem_1fr]">
                <dt class="text-gray-500 dark:text-gray-400">{{ $fields[$key] ?? $key }}</dt>
                <dd class="whitespace-pre-line break-words font-medium">{{ is_scalar($value) ? (is_bool($value) ? ($value ? 'بله' : 'خیر') : $value) : json_encode($value, JSON_UNESCAPED_UNICODE) }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="grid gap-2 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
            <div class="mb-1 text-xs text-gray-500 dark:text-gray-400">فرستنده</div>
            @if ($contact)
                <a href="{{ ContactResource::getUrl('view', ['record' => $contact]) }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">{{ $contact->displayName() }}</a>
                <div class="mt-1 flex flex-wrap gap-x-3 text-xs text-gray-600 dark:text-gray-300">
                    @if ($contact->mobile) <a href="tel:{{ $contact->mobile }}" dir="ltr">{{ PersianText::toPersianDigits($contact->mobile) }}</a> @endif
                    @if ($contact->email) <a href="mailto:{{ $contact->email }}" dir="ltr">{{ $contact->email }}</a> @endif
                    @if ($contact->customer_id) <span>· مشتری سایت</span> @endif
                    <span>· {{ PersianText::toPersianDigits($contact->submissions_count) }} فرم</span>
                </div>
            @else
                <span class="text-gray-500">بدون شماره یا ایمیل</span>
            @endif
        </div>
        <div class="rounded-xl border border-gray-200 p-3 text-xs text-gray-600 dark:border-white/10 dark:text-gray-300">
            <div>{{ Jalali::format($submission->created_at) }} — ساعت {{ Jalali::time($submission->created_at) }}</div>
            @if ($submission->page_url) <div class="mt-1 truncate" dir="ltr"><a href="{{ $submission->page_url }}" target="_blank" class="hover:underline">{{ urldecode($submission->page_url) }}</a></div> @endif
            @if ($submission->utm) <div class="mt-1" dir="ltr">{{ collect($submission->utm)->map(fn ($v, $k) => $k.'='.$v)->implode(' · ') }}</div> @endif
            @if ($subjectUrl) <div class="mt-2"><a href="{{ $subjectUrl }}" class="font-medium text-primary-600 hover:underline dark:text-primary-400">مشاهده‌ی مورد مربوط ←</a></div> @endif
        </div>
    </div>
</div>
