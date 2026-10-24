{{-- «تنظیمات فروشگاه» opened from the admin search: scroll to the field and flash it (?tab=sms&field=driver) | نویسنده: علی فصیحی (Ali Fasihi) --}}
@if (($field = request()->query('field')) && ($tab = request()->query('tab')))
    <script>
        (() => {
            const path = @js($tab.'.'.$field);
            const ends = (el) => [...el.attributes].some((a) => (a.name === 'id' || a.name.startsWith('wire:model')) && a.value.endsWith(path));
            // the input itself first (wire:model="data.sms.driver" / id="form.sms.driver"), then any element with that id
            const find = () => [...document.querySelectorAll('input, select, textarea')].find(ends) ?? [...document.querySelectorAll('[id]')].find(ends);
            let tries = 0;
            const jump = () => {
                const el = find();
                if (! el) return ++tries < 20 && setTimeout(jump, 150);
                const box = el.closest('.fi-fo-field') ?? el;
                box.scrollIntoView({ behavior: 'smooth', block: 'center' });
                box.style.transition = 'box-shadow .3s';
                box.style.borderRadius = '0.75rem';
                box.style.boxShadow = '0 0 0 3px rgb(204 51 102 / .55)';
                setTimeout(() => (box.style.boxShadow = ''), 2600);
                el.focus?.({ preventScroll: true });
            };
            document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', jump) : jump();
        })();
    </script>
@endif
