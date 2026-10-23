/**
 * Khaneh Romantic — Web push on the page: asks politely (our own small card first, the browser's
 * permission dialog only after «بله»), keeps the subscription in sync (and tied to the logged-in
 * customer), and runs the [data-push-subscribe] buttons (e.g. «خبر ارسال سفارش» on an order).
 * On iPhone, push works only for the site added to the home screen — the buttons explain that.
 * Loaded only when the Push module is on (#push-config); a separate small file.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

const LATER = 'kr-push-later';
const VIEWS = 'kr-push-views';
const SYNCED = 'kr-push-sync';

const store = {
    get: (k) => { try { return localStorage.getItem(k); } catch { return null; } },
    set: (k, v) => { try { localStorage.setItem(k, v); } catch { /* private mode */ } },
};
const toast = (message) => window.dispatchEvent(new CustomEvent('toast', { detail: { message } }));

function keyBytes(base64url) {
    const b64 = (base64url + '='.repeat((4 - (base64url.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(b64), (c) => c.charCodeAt(0));
}

function sameKey(subscription, key) {
    const current = subscription?.options?.applicationServerKey;
    if (!current) return true;   // older browsers don't tell: assume it's ours
    const a = new Uint8Array(current);
    return a.length === key.length && a.every((v, i) => v === key[i]);
}

export default async function push() {
    const config = JSON.parse(document.getElementById('push-config')?.textContent || 'null');
    if (!config) return;

    const buttons = [...document.querySelectorAll('[data-push-button]')];
    if (!('PushManager' in window) || !('Notification' in window)) {
        if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
            document.querySelectorAll('[data-push-ios]').forEach((el) => el.classList.remove('hidden'));
        }
        return;
    }

    const registration = await navigator.serviceWorker.ready;
    const key = keyBytes(config.key);

    const send = async (subscription) => {
        const res = await fetch(config.subscribe, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.token },
            body: JSON.stringify({ subscription: subscription.toJSON() }),
            credentials: 'same-origin',
        });
        if (res.ok) store.set(SYNCED, JSON.stringify({ e: subscription.endpoint, c: config.customer, t: Date.now() }));
        return res.ok;
    };

    const current = async () => {
        let subscription = await registration.pushManager.getSubscription();
        if (subscription && !sameKey(subscription, key)) {   // the shop's keys changed
            await subscription.unsubscribe();
            subscription = null;
        }
        return subscription || registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: key });
    };

    const markDone = () => buttons.forEach((b) => {
        b.classList.remove('hidden');
        b.disabled = true;
        b.querySelector('span').textContent = b.dataset.pushDone;
    });

    const subscribe = async () => {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            toast(permission === 'denied' ? 'نوتیفیکیشن در تنظیمات مرورگر بسته است.' : 'باشد، هر وقت خواستید.');
            return false;
        }
        try {
            const ok = await send(await current());
            if (ok) { markDone(); toast('نوتیفیکیشن روشن شد'); }
            return ok;
        } catch {
            toast('روشن کردن نوتیفیکیشن نشد؛ کمی بعد دوباره امتحان کنید.');
            return false;
        }
    };

    // already allowed: keep the subscription fresh and tied to whoever is logged in
    if (Notification.permission === 'granted') {
        markDone();
        try {
            const subscription = await current();
            const synced = JSON.parse(store.get(SYNCED) || '{}');
            if (synced.e !== subscription.endpoint || synced.c !== config.customer || Date.now() - (synced.t || 0) > 7 * 864e5) {
                await send(subscription);
            }
        } catch { /* try next time */ }
        return;
    }

    if (Notification.permission === 'default') {
        buttons.forEach((b) => b.classList.remove('hidden'));
    }
    document.addEventListener('click', (e) => {
        const button = e.target.closest('[data-push-subscribe]');
        if (button) { e.preventDefault(); subscribe(); }
    });

    // our own small card, after a few pages and some seconds — never the browser dialog out of the blue
    const prompt = config.prompt;
    const views = Number(store.get(VIEWS) || 0) + 1;
    store.set(VIEWS, String(views));
    const later = Number(store.get(LATER) || 0);
    if (!prompt.on || Notification.permission !== 'default' || views < prompt.views || Date.now() - later < prompt.days * 864e5) return;

    setTimeout(() => {
        const tpl = document.getElementById('push-prompt-tpl');
        if (!tpl || document.querySelector('.exit-popup, .chat-panel:not([hidden])')) return;
        const card = tpl.content.firstElementChild.cloneNode(true);
        card.style.opacity = '0';
        card.style.transform = 'translateY(16px)';
        document.body.appendChild(card);
        requestAnimationFrame(() => {
            card.style.transition = 'opacity .3s ease, transform .35s cubic-bezier(.2,.9,.3,1.2)';
            card.style.opacity = '1';
            card.style.transform = 'none';
        });
        const close = () => { card.style.opacity = '0'; setTimeout(() => card.remove(), 300); };
        card.querySelector('[data-push-later]').addEventListener('click', () => { store.set(LATER, String(Date.now())); close(); });
        card.querySelector('[data-push-yes]').addEventListener('click', async () => {
            close();
            if (!(await subscribe())) store.set(LATER, String(Date.now()));
        });
    }, prompt.delay * 1000);
}
