import '@fontsource-variable/inter';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;
Chart.defaults.color = '#6B7280';
Chart.defaults.borderColor = 'rgba(17,24,39,.06)';
/* ---------- تأكيدات أنيقة ----------
   تحوّل تلقائياً onsubmit/onclick="return confirm('...')" إلى نافذة تأكيد */
const CONFIRM_RE = /confirm\(\s*(['"])([\s\S]*?)\1\s*\)/;

function askConfirm(message, onConfirm) {
    window.dispatchEvent(new CustomEvent('confirm-request', { detail: { message, onConfirm } }));
}

function convertInlineConfirms() {
    document
        .querySelectorAll('form[onsubmit*="confirm("], button[onclick*="confirm("], a[onclick*="confirm("]')
        .forEach((el) => {
            const attr = el.hasAttribute('onsubmit') ? 'onsubmit' : 'onclick';
            const m = el.getAttribute(attr).match(CONFIRM_RE);

            if (m) {
                el.dataset.confirm = m[2];
                el.removeAttribute(attr);
            }
        });
}

document.addEventListener('DOMContentLoaded', convertInlineConfirms);

// أزرار وروابط
document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-confirm]');

    if (!el || el.tagName === 'FORM') return;

    if (el.dataset.confirmed === '1') {
        el.dataset.confirmed = '';
        return;
    }

    e.preventDefault();
    e.stopPropagation();
    askConfirm(el.dataset.confirm, () => {
        el.dataset.confirmed = '1';
        el.click();
    });
}, true);

// نماذج: تأكيد + منع الإرسال المزدوج
document.addEventListener('submit', (e) => {
    const form = e.target;

    if (form.dataset.confirm && form.dataset.confirmed !== '1') {
        e.preventDefault();
        e.stopImmediatePropagation();
        askConfirm(form.dataset.confirm, () => {
            form.dataset.confirmed = '1';
            form.requestSubmit();
        });

        return;
    }

    form.dataset.confirmed = '';

    if ((form.method || 'get').toLowerCase() === 'post') {
        if (form.dataset.submitting === '1') {
            e.preventDefault();

            return;
        }

        form.dataset.submitting = '1';

        setTimeout(() => {
            form.querySelectorAll('button:not([type="button"]), input[type="submit"]').forEach((b) => {
                b.disabled = true;
                b.classList.add('opacity-60', 'cursor-wait');
            });
        }, 0);
    }
}, true);

// زر «رجوع» في المتصفح: أعد تفعيل الأزرار
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-submitting="1"]').forEach((f) => {
        f.dataset.submitting = '';
        f.querySelectorAll('button, input[type="submit"]').forEach((b) => {
            b.disabled = false;
            b.classList.remove('opacity-60', 'cursor-wait');
        });
    });
});

Alpine.start();
