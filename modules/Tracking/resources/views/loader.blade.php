{{-- Analytics & pixels, added late and async: after the page has loaded and the visitor first moves (or a few
     seconds later). With international internet cut, they fail quietly in the background. | نویسنده: علی فصیحی (Ali Fasihi) --}}
@php($tracking = \Modules\Tracking\Support\Tracking::scripts())
@if ($tracking)
    @if (! empty($tracking['custom']))
        <template id="tr-custom">{!! $tracking['custom'] !!}</template>
    @endif
    <script>
    (() => {
        const c = @json(\Illuminate\Support\Arr::except($tracking, ['custom']));
        if (navigator.connection && navigator.connection.saveData) return;
        let done = false;
        const add = (src) => { const s = document.createElement('script'); s.async = true; s.src = src; document.head.appendChild(s); };
        const go = () => {
            if (done) return; done = true;
            window.dataLayer = window.dataLayer || [];
            if (c.gtm) { dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' }); add('https://www.googletagmanager.com/gtm.js?id=' + c.gtm); }
            if (c.ga4) { window.gtag = function () { dataLayer.push(arguments); }; gtag('js', new Date()); gtag('config', c.ga4); add('https://www.googletagmanager.com/gtag/js?id=' + c.ga4); }
            if (c.clarity) { window.clarity = window.clarity || function () { (clarity.q = clarity.q || []).push(arguments); }; add('https://www.clarity.ms/tag/' + c.clarity); }
            if (c.pixel) {
                const f = window; if (!f.fbq) { const n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; f._fbq = n; n.push = n; n.loaded = true; n.version = '2.0'; n.queue = []; }
                fbq('init', c.pixel); fbq('track', 'PageView'); add('https://connect.facebook.net/en_US/fbevents.js');
            }
            const t = document.getElementById('tr-custom');
            if (t) for (const node of t.content.childNodes) {
                if (node.nodeName === 'SCRIPT') {
                    const s = document.createElement('script');
                    for (const a of node.attributes) s.setAttribute(a.name, a.value);
                    if (node.src) s.async = true; else s.text = node.textContent;
                    document.head.appendChild(s);
                } else if (node.nodeType === 1) document.body.appendChild(node.cloneNode(true));
            }
        };
        const arm = () => {
            for (const e of ['scroll', 'pointerdown', 'pointermove', 'keydown', 'touchstart']) addEventListener(e, go, { once: true, passive: true });
            setTimeout(go, c.delay);
        };
        document.readyState === 'complete' ? arm() : addEventListener('load', arm, { once: true });
    })();
    </script>
@endif
