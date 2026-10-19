{{-- Hook «post.after»: comments | نویسنده: علی فصیحی (Ali Fasihi) --}}
<div class="mx-auto mt-6 max-w-4xl px-3 sm:px-4">
    <livewire:comments.section :model="$post" :key="'comments-'.$post->id" />
</div>
