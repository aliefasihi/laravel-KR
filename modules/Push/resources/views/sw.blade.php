{{-- Web push part of the service worker (included by pwa::sw when the Push module is on) | نویسنده: علی فصیحی (Ali Fasihi) --}}
const PUSH_ICON = @json($icon);

self.addEventListener('push', (event) => {
    let data = {};
    try { data = event.data ? event.data.json() : {}; } catch { data = { body: event.data?.text() }; }
    event.waitUntil(self.registration.showNotification(data.title || @json(config('khaneh.store.name')), {
        body: data.body || '',
        icon: data.icon || PUSH_ICON,
        badge: PUSH_ICON,
        image: data.image,
        tag: data.tag,
        renotify: !!data.tag,
        dir: 'rtl',
        lang: 'fa',
        data: { url: data.url || '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = new URL(event.notification.data?.url || '/', self.location.origin).href;
    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const client of windows) {
            if (client.url === url && 'focus' in client) return client.focus();
        }
        return self.clients.openWindow(url);
    })());
});

// the browser replaced the subscription: tell the shop (the old endpoint proves it's ours)
self.addEventListener('pushsubscriptionchange', (event) => {
    event.waitUntil((async () => {
        const options = event.oldSubscription?.options || { userVisibleOnly: true, applicationServerKey: @json(\Modules\Push\Support\WebPush::keys()['public']) };
        const fresh = event.newSubscription || await self.registration.pushManager.subscribe(options);
        if (!event.oldSubscription) return;
        await fetch(@json(route('push.renew', absolute: false)), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ old_endpoint: event.oldSubscription.endpoint, subscription: fresh.toJSON() }),
        });
    })());
});
