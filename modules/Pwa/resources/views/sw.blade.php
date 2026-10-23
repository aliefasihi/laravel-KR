{{-- Service worker | نویسنده: علی فصیحی (Ali Fasihi) --}}
/* Khaneh Romantic service worker ({{ $version }}) — نویسنده: علی فصیحی (Ali Fasihi)
 * Pages always come from the network (prices, stock and the cart must be live); without a
 * connection the offline page shows. Built CSS/JS and fonts: cache first (their names change
 * with every build). Product images: shown from the cache at once and refreshed behind.
 * Never touched: the admin, Livewire, payments, anything not GET. */
@if (! $enabled)
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil((async () => {
    for (const key of await caches.keys()) { if (key.startsWith('kr-')) await caches.delete(key); }
    await self.registration.unregister();
})()));
@else
const VERSION = @json($version);
const STATIC = 'kr-static-' + VERSION;
const IMAGES = 'kr-images';
const OFFLINE = @json($offline);
const PRECACHE = [OFFLINE, @json($icon), '/fonts/fonts.css', '/fonts/vazirmatn/vazirmatn-arabic-wght-normal.woff2'];
const SKIP = /^\/(admin|livewire|filament|payment|orders\/print|cart\/back|sw\.js)/;
const MAX_IMAGES = 150;

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        for (const key of await caches.keys()) {
            if (key.startsWith('kr-') && key !== STATIC && key !== IMAGES) await caches.delete(key);
        }
        await self.clients.claim();
    })());
});

async function trim(cache) {
    const keys = await cache.keys();
    for (let i = 0; i < keys.length - MAX_IMAGES; i++) await cache.delete(keys[i]);
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;
    const url = new URL(request.url);
    const local = url.origin === self.location.origin;
    if (local && SKIP.test(url.pathname)) return;

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(async () => (await caches.match(OFFLINE)) || Response.error()));
        return;
    }

    if (local && (url.pathname.startsWith('/build/') || url.pathname.startsWith('/fonts/'))) {
        event.respondWith(caches.open(STATIC).then(async (cache) => {
            const hit = await cache.match(request);
            if (hit) return hit;
            const response = await fetch(request);
            if (response.ok) cache.put(request, response.clone());
            return response;
        }));
        return;
    }

    if (request.destination === 'image' && (local || url.protocol === 'https:')) {
        event.respondWith(caches.open(IMAGES).then(async (cache) => {
            const hit = await cache.match(request);
            const refresh = fetch(request).then((response) => {
                if (response.ok || response.type === 'opaque') cache.put(request, response.clone()).then(() => trim(cache));
                return response;
            }).catch(() => hit);
            if (hit) { event.waitUntil(refresh); return hit; }
            return refresh;
        }));
    }
});
@if (class_exists(\Modules\Push\Support\Push::class) && \App\Core\Modules\Modules::enabled('Push') && \Modules\Push\Support\Push::enabled())

@include('push::sw')
@endif
@endif
