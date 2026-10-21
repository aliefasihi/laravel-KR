/**
 * Khaneh Romantic — Occasion effects: pieces drifting in from the sides of the page
 * (pomegranate seeds for Yalda, blossoms for Nowruz, sparks for Chaharshanbe-Suri, bats…).
 * Loaded only when an occasion is on (html[data-occasion]); a separate chunk, ~3 KB.
 * Nothing moves for visitors who asked their device for less motion, while the tab is hidden,
 * or after they press the small «خاموش کردن افکت» button (remembered per occasion).
 * نویسنده: علی فصیحی (Ali Fasihi)
 */

const OFF_KEY = 'kr-occ-off';

export default function occasion() {
    const root = document.documentElement;
    const config = JSON.parse(document.getElementById('occ-config')?.textContent || 'null');
    if (!config) return;

    const toggle = document.getElementById('occ-toggle');
    let stop = null;

    const isOff = () => {
        try { return localStorage.getItem(OFF_KEY) === config.key; } catch { return false; }
    };
    const setOff = (off) => {
        try { off ? localStorage.setItem(OFF_KEY, config.key) : localStorage.removeItem(OFF_KEY); } catch { /* private mode */ }
        root.classList.toggle('occ-off', off);
        toggle?.setAttribute('aria-pressed', String(!off));
        toggle?.setAttribute('title', off ? 'روشن کردن افکت‌های مناسبت' : 'خاموش کردن افکت‌های مناسبت');
        if (off) { stop?.(); stop = null; } else if (!stop) { stop = start(config); }
    };

    toggle?.addEventListener('click', () => setOff(!root.classList.contains('occ-off')));

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (isOff()) setOff(true);
    else if (!reduced) stop = start(config);
}

function start(config) {
    const canvas = document.createElement('canvas');
    canvas.className = 'occ-canvas';
    canvas.setAttribute('aria-hidden', 'true');
    document.body.appendChild(canvas);
    const ctx = canvas.getContext('2d');

    const mobile = window.matchMedia('(max-width: 767px)').matches;
    const max = mobile ? 10 : 30;
    const every = mobile ? 2600 : 1300;           // ms between new pieces after the opening burst
    const burst = mobile ? 8 : 24;
    let width = 0, height = 0, dpr = 1;

    const resize = () => {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        width = window.innerWidth; height = window.innerHeight;
        canvas.width = width * dpr; canvas.height = height * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    resize();
    window.addEventListener('resize', resize);

    // pieces: images from the SVG art, picked by weight (or procedural sparks)
    const pool = [];
    for (const bit of config.bits) {
        const img = bit.src ? Object.assign(new Image(), { src: bit.src }) : null;
        for (let i = 0; i < bit.weight; i++) pool.push({ img, spark: !bit.src });
    }

    const rand = (a, b) => a + Math.random() * (b - a);
    const pieces = [];

    const spawn = () => {
        if (pieces.length >= max) return;
        const kind = pool[Math.floor(Math.random() * pool.length)];
        const fromLeft = Math.random() < 0.5;
        const size = kind.spark ? rand(3, 6) : rand(mobile ? 10 : 15, mobile ? 17 : 27);
        const p = { kind, size, t: 0, rot: rand(0, Math.PI * 2), spin: rand(-0.02, 0.02), sway: rand(0.6, 1.6), phase: rand(0, 6.28), alpha: 0 };

        if (config.motion === 'fly') {                       // bats: across the upper half, wings flapping
            p.x = fromLeft ? -40 : width + 40; p.y = rand(height * 0.12, height * 0.6);
            p.vx = (fromLeft ? 1 : -1) * rand(1.2, 2.4); p.vy = rand(-0.2, 0.2); p.spin = 0; p.rot = 0;
            p.size = rand(26, 40);
        } else if (config.motion === 'rise') {               // sparks: up from the bottom corners
            p.x = fromLeft ? rand(0, width * 0.18) : rand(width * 0.82, width); p.y = height + 10;
            p.vx = (fromLeft ? 1 : -1) * rand(0.1, 0.6); p.vy = -rand(1, 2.4);
            p.life = rand(140, 260);
        } else {                                             // fall: in from the sides, drifting toward the content
            p.x = fromLeft ? rand(-20, width * 0.12) : rand(width * 0.88, width + 20); p.y = rand(-40, height * 0.25);
            p.vx = (fromLeft ? 1 : -1) * rand(0.15, 0.7); p.vy = rand(0.5, 1.3);
        }
        pieces.push(p);
    };

    for (let i = 0; i < burst; i++) setTimeout(spawn, i * 90);
    let timer = setInterval(() => { if (!document.hidden) spawn(); }, every);

    let frame = 0, running = true;
    const tick = () => {
        if (!running) return;
        ctx.clearRect(0, 0, width, height);
        for (let i = pieces.length - 1; i >= 0; i--) {
            const p = pieces[i];
            p.t++;
            p.alpha = Math.min(1, p.alpha + 0.04);
            if (config.motion === 'fly') {
                p.x += p.vx; p.y += p.vy + Math.sin((p.t + p.phase * 20) / 22) * 0.6;
            } else if (config.motion === 'rise') {
                p.x += p.vx + Math.sin(p.t / 14 + p.phase) * 0.5; p.y += p.vy;
                p.alpha = Math.max(0, 1 - p.t / p.life);
            } else {
                p.x += p.vx + Math.sin(p.t / 40 + p.phase) * p.sway * 0.5; p.y += p.vy; p.rot += p.spin;
            }

            const gone = p.y > height + 50 || p.y < -60 || p.x < -80 || p.x > width + 80 || (p.life && p.t > p.life);
            if (gone) { pieces.splice(i, 1); continue; }

            ctx.save();
            ctx.globalAlpha = p.alpha * 0.92;
            ctx.translate(p.x, p.y);
            if (p.kind.spark) {
                const g = ctx.createRadialGradient(0, 0, 0, 0, 0, p.size * 2.4);
                g.addColorStop(0, 'rgba(255,247,200,1)'); g.addColorStop(0.35, 'rgba(251,191,36,.9)'); g.addColorStop(1, 'rgba(234,88,12,0)');
                ctx.fillStyle = g; ctx.beginPath(); ctx.arc(0, 0, p.size * 2.4, 0, Math.PI * 2); ctx.fill();
            } else if (p.kind.img?.complete) {
                ctx.rotate(p.rot);
                if (config.motion === 'fly') ctx.scale(p.vx < 0 ? -1 : 1, 0.55 + Math.abs(Math.sin(p.t / 5)) * 0.45);
                ctx.drawImage(p.kind.img, -p.size / 2, -p.size / 2, p.size, p.size);
            }
            ctx.restore();
        }
        frame = requestAnimationFrame(tick);
    };
    frame = requestAnimationFrame(tick);

    return () => {
        running = false;
        cancelAnimationFrame(frame);
        clearInterval(timer);
        window.removeEventListener('resize', resize);
        canvas.remove();
    };
}
