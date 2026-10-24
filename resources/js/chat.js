/**
 * Khaneh Romantic — The site chat panel. Loaded on the first click of the chat button (a separate small
 * file), keeps the conversation in this browser (token) across pages, renders the assistant's answers:
 * text, product cards, links and quick replies. Everything is put in with textContent (no HTML from the server).
 * «منو» in the header (and a chip under answers that have no choices of their own) goes back to the bot's menu.
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

const KEY = 'kr-chat';

const store = {
    get: () => { try { return localStorage.getItem(KEY); } catch { return null; } },
    set: (v) => { try { localStorage.setItem(KEY, v); } catch { /* private mode */ } },
};

const el = (tag, cls, text) => {
    const node = document.createElement(tag);
    if (cls) node.className = cls;
    if (text != null) node.textContent = text;
    return node;
};

const safeUrl = (url) => (/^(\/(?!\/)|https:\/\/|tel:)/.test(url || '') ? url : null);

let panel = null;

export function open(launcher) {
    const config = JSON.parse(document.getElementById('chat-config')?.textContent || 'null');
    if (!config) return;
    if (panel) {
        panel.hidden = !panel.hidden;
        launcher.setAttribute('aria-expanded', String(!panel.hidden));
        if (!panel.hidden) panel.querySelector('textarea')?.focus();
        return;
    }

    panel = document.getElementById('chat-tpl').content.firstElementChild.cloneNode(true);
    document.body.appendChild(panel);
    launcher.setAttribute('aria-expanded', 'true');
    const log = panel.querySelector('[data-chat-log]');
    const quick = panel.querySelector('[data-chat-quick]');
    const form = panel.querySelector('[data-chat-form]');
    const input = form.querySelector('textarea');
    const button = form.querySelector('button[type="submit"]');
    let busy = false;

    const close = () => { panel.hidden = true; launcher.setAttribute('aria-expanded', 'false'); launcher.focus(); };
    panel.querySelector('[data-chat-close]').addEventListener('click', close);
    panel.querySelector('[data-chat-menu]')?.addEventListener('click', () => send(config.menu));
    panel.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

    const scroll = () => requestAnimationFrame(() => { log.scrollTop = log.scrollHeight; });

    const render = (m) => {
        const mine = m.role === 'user';
        const row = el('div', 'flex ' + (mine ? 'justify-start' : 'justify-end'));
        const bubble = el('div', 'max-w-[85%] space-y-2 rounded-2xl px-3.5 py-2.5 text-sm leading-7 shadow-sm ' +
            (mine ? 'rounded-br-md bg-rose text-white' : 'rounded-bl-md bg-surface text-ink ring-1 ring-line'));
        if (m.text) bubble.appendChild(el('p', 'whitespace-pre-line', m.text));

        for (const p of m.products || []) {
            const card = el('a', 'flex items-center gap-3 rounded-xl bg-page p-2 text-ink ring-1 ring-line transition hover:ring-rose');
            card.href = safeUrl(p.url) || '#';
            if (p.image) {
                const img = el('img', 'size-14 shrink-0 rounded-lg object-cover');
                img.src = p.image; img.alt = ''; img.loading = 'lazy';
                card.appendChild(img);
            }
            const info = el('span', 'min-w-0 flex-1');
            info.appendChild(el('span', 'line-clamp-2 block text-xs font-bold leading-5 text-navy', p.name));
            const price = el('span', 'mt-0.5 block text-xs font-black ' + (p.stock === false ? 'text-subtle' : 'text-rose'), p.stock === false ? 'ناموجود' : (p.price || ''));
            if (p.was && p.stock !== false) price.appendChild(el('s', 'ms-1 font-normal text-subtle', p.was));
            info.appendChild(price);
            card.appendChild(info);
            bubble.appendChild(card);
        }
        const links = (m.links || []).filter((l) => safeUrl(l.url));
        if (links.length) {
            const wrap = el('div', 'flex flex-wrap gap-1.5');
            for (const l of links) {
                const a = el('a', 'inline-flex rounded-full bg-navy/5 px-3 py-1 text-xs font-bold text-navy hover:bg-rose hover:text-white', l.label);
                a.href = l.url;
                if (/^https:/.test(l.url)) { a.target = '_blank'; a.rel = 'noopener'; }
                wrap.appendChild(a);
            }
            bubble.appendChild(wrap);
        }
        row.appendChild(bubble);
        log.appendChild(row);

        quick.replaceChildren();
        // an answer without its own choices still offers the way back to the menu
        const choices = m.quick?.length ? m.quick : (mine || !config.menu ? [] : [config.menu]);
        for (const label of choices) {
            const chip = el('button', 'shrink-0 rounded-full border border-rose/30 bg-surface px-3 py-1.5 text-xs font-bold text-rose hover:bg-rose hover:text-white', label);
            chip.type = 'button';
            chip.addEventListener('click', () => send(label));
            quick.appendChild(chip);
        }
        scroll();
    };

    const typing = () => {
        const row = el('div', 'flex justify-end');
        const dots = el('div', 'rounded-2xl rounded-bl-md bg-surface px-4 py-3 ring-1 ring-line');
        dots.innerHTML = '<span class="inline-flex gap-1"><span class="size-1.5 animate-bounce rounded-full bg-muted"></span><span class="size-1.5 animate-bounce rounded-full bg-muted [animation-delay:.15s]"></span><span class="size-1.5 animate-bounce rounded-full bg-muted [animation-delay:.3s]"></span></span>';
        row.appendChild(dots);
        log.appendChild(row);
        scroll();
        return () => row.remove();
    };

    const send = async (text) => {
        text = (text || '').trim();
        if (!text || busy) return;
        busy = true;
        button.disabled = true;
        quick.replaceChildren();
        render({ role: 'user', text });
        const stop = typing();
        try {
            const res = await fetch(config.send, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.token },
                body: JSON.stringify({ text, token: store.get(), page: location.pathname }),
                credentials: 'same-origin',
            });
            const data = await res.json();
            stop();
            if (data.token) store.set(data.token);
            (data.messages || []).forEach(render);
            if (!res.ok && !(data.messages || []).length) render({ role: 'bot', text: res.status === 429 ? 'کمی آرام‌تر 🙂 چند لحظه بعد دوباره بنویسید.' : 'ارسال نشد؛ دوباره امتحان کنید.' });
        } catch {
            stop();
            render({ role: 'bot', text: 'ارتباط برقرار نشد؛ اینترنت را بررسی کنید.' });
        } finally {
            busy = false;
            button.disabled = false;
        }
    };

    form.addEventListener('submit', (e) => { e.preventDefault(); const t = input.value; input.value = ''; input.style.height = ''; send(t); });
    input.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
    input.addEventListener('input', () => { input.style.height = ''; input.style.height = Math.min(input.scrollHeight, 112) + 'px'; });

    const stop = typing();
    fetch(config.history + '?token=' + encodeURIComponent(store.get() || ''), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then((r) => r.json())
        .then((data) => { stop(); (data.messages || []).forEach(render); })
        .catch(() => { stop(); render({ role: 'bot', text: 'سلام! چه کمکی از دستم برمی‌آید؟' }); });

    if (matchMedia('(min-width: 640px)').matches) input.focus();
}
