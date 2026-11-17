/* سامانه کلید (KEY) — تعاملات سمت کاربر */
(function () {
    'use strict';

    // ---------------- حالت روشن/تاریک ----------------
    const THEME_KEY = 'key-theme';

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem(THEME_KEY, theme);
        document.querySelectorAll('.theme-toggle i').forEach(function (i) {
            i.classList.toggle('bi-moon-stars', theme === 'light');
            i.classList.toggle('bi-sun', theme === 'light' === false);
            i.classList.toggle('bi-sun', theme !== 'light');
        });
    }

    applyTheme(localStorage.getItem(THEME_KEY) || 'light');

    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('.theme-toggle');
        if (toggle) {
            const current = document.documentElement.getAttribute('data-bs-theme');
            applyTheme(current === 'dark' ? 'light' : 'dark');
        }

        // ---------------- سایدبار موبایل ----------------
        const menuBtn = e.target.closest('.key-menu-toggle');
        if (menuBtn) {
            document.querySelector('.key-sidebar')?.classList.toggle('show');
        }
        const overlay = e.target.closest('.sidebar-overlay');
        if (overlay) {
            document.querySelector('.key-sidebar')?.classList.remove('show');
        }
    });

    // ---------------- درخواست‌های fetch با CSRF ----------------
    window.keyFetch = async function (url, options) {
        options = options || {};
        options.headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
        }, options.headers || {});

        if (options.json) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.json);
            delete options.json;
        }
        const res = await fetch(url, options);
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw Object.assign(new Error(data.message || ('HTTP ' + res.status)), { data: data, status: res.status });
        }
        return data;
    };

    // ---------------- توست ساده ----------------
    window.keyToast = function (message, type) {
        type = type || 'success';
        let holder = document.querySelector('#toast-holder');
        if (!holder) {
            holder = document.createElement('div');
            holder.id = 'toast-holder';
            holder.className = 'toast-container position-fixed bottom-0 start-0 p-3';
            holder.style.zIndex = '2000';
            document.body.appendChild(holder);
        }
        const el = document.createElement('div');
        el.className = 'toast align-items-center border-0 show text-bg-' + type;
        el.setAttribute('role', 'alert');
        el.innerHTML = '<div class="d-flex"><div class="toast-body">' + message + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-auto m-auto" data-bs-dismiss="toast"></button></div>';
        holder.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    };

    // ---------------- تأیید حذف ----------------
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form.matches('[data-confirm]')) {
            if (!confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        }
    });

    // ---------------- شمارش معکوس آزمون ----------------
    window.keyCountdown = function (el, seconds, onExpire) {
        let remaining = seconds;
        const render = function () {
            const h = String(Math.floor(remaining / 3600)).padStart(2, '0');
            const m = String(Math.floor((remaining % 3600) / 60)).padStart(2, '0');
            const s = String(remaining % 60).padStart(2, '0');
            el.textContent = h + ':' + m + ':' + s;
        };
        render();
        const timer = setInterval(function () {
            remaining--;
            if (remaining <= 0) {
                clearInterval(timer);
                el.textContent = '۰۰:۰۰:۰۰';
                if (onExpire) onExpire();
            } else {
                render();
                if (remaining < 300) el.classList.add('text-danger');
            }
        }, 1000);
        return timer;
    };

    // ---------------- انتخاب گزینه (بدون jQuery) ----------------
    document.addEventListener('click', function (e) {
        const item = e.target.closest('.option-item[data-selectable]');
        if (item) {
            const block = item.closest('.question-card');
            block.querySelectorAll('.option-item').forEach(function (o) { o.classList.remove('selected'); });
            item.classList.add('selected');
        }
    });

    // ---------------- ورودی مبلغ با فرمت هزارگان ----------------
    document.querySelectorAll('input[data-money]').forEach(function (input) {
        input.addEventListener('input', function () {
            const raw = input.value.replace(/[^\d]/g, '');
            input.value = raw ? Number(raw).toLocaleString('en-US') : '';
        });
    });
})();
