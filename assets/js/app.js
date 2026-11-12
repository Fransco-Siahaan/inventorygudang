/* Perilaku bersama untuk seluruh halaman.
   Semua teks yang berasal dari server dimasukkan lewat textContent (bukan innerHTML). */
(function () {
    'use strict';

    /* ---------- Sidebar (mobile) ---------- */
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('backdrop');
    const btnMenu = document.getElementById('btnMenu');

    function setSidebar(open) {
        if (!sidebar) return;
        sidebar.classList.toggle('open', open);
        if (backdrop) backdrop.classList.toggle('show', open);
    }
    if (btnMenu) btnMenu.addEventListener('click', () => setSidebar(!sidebar.classList.contains('open')));
    if (backdrop) backdrop.addEventListener('click', () => setSidebar(false));

    /* ---------- Format angka ---------- */
    window.idn = (n) => (Number(n) || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    window.rp = (n) => 'Rp ' + (Math.round(Number(n) || 0)).toLocaleString('id-ID');

    /* ---------- Toast ---------- */
    const toastWrap = document.createElement('div');
    toastWrap.className = 'toasts';
    toastWrap.setAttribute('aria-live', 'polite');
    document.body.appendChild(toastWrap);

    const ICONS = {
        success: 'fa-circle-check',
        error: 'fa-circle-exclamation',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };
    const TITLES = { success: 'Berhasil', error: 'Perhatian', warning: 'Peringatan', info: 'Info' };

    window.toast = function (type, msg, title) {
        const t = type in ICONS ? type : 'info';
        const el = document.createElement('div');
        el.className = 'toast ' + t;
        el.setAttribute('role', t === 'error' ? 'alert' : 'status');

        const icon = document.createElement('i');
        icon.className = 'fa-solid ' + ICONS[t];
        icon.setAttribute('aria-hidden', 'true');

        const body = document.createElement('div');
        const h = document.createElement('span');
        h.className = 't-title';
        h.textContent = title || TITLES[t];
        const p = document.createElement('span');
        p.className = 't-msg';
        p.textContent = msg;
        body.append(h, p);

        const close = document.createElement('button');
        close.className = 'x';
        close.type = 'button';
        close.setAttribute('aria-label', 'Tutup notifikasi');
        close.textContent = '×';
        close.addEventListener('click', () => removeToast(el));

        el.append(icon, body, close);
        toastWrap.appendChild(el);
        setTimeout(() => removeToast(el), t === 'error' ? 7000 : 4500);
    };

    function removeToast(el) {
        if (!el.isConnected || el.classList.contains('leave')) return;
        el.classList.add('leave');
        setTimeout(() => el.remove(), 260);
    }

    // Tampilkan notifikasi dari session (flash) setelah halaman dimuat
    (window.FLASH || []).forEach((f) => toast(f.type, f.msg));

    /* ---------- Modal ---------- */
    window.openModal = function (id) {
        const m = document.getElementById(id);
        if (m) m.classList.add('show');
    };
    window.closeModal = function (id) {
        const m = document.getElementById(id);
        if (m) m.classList.remove('show');
    };

    document.addEventListener('click', (e) => {
        const closer = e.target.closest('[data-close]');
        if (closer) closeModal(closer.dataset.close);
        if (e.target.classList && e.target.classList.contains('modal-backdrop')) {
            e.target.classList.remove('show');
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.show').forEach((m) => m.classList.remove('show'));
            setSidebar(false);
        }
    });

    /* ---------- Konfirmasi sebelum submit (form ber-atribut data-confirm) ---------- */
    let pendingForm = null;
    const confirmText = document.getElementById('confirmText');
    const confirmOk = document.getElementById('confirmOk');

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form.dataset.confirm || form.dataset.confirmed === '1') return;
        e.preventDefault();
        pendingForm = form;
        if (confirmText) confirmText.textContent = form.dataset.confirm;
        openModal('confirmModal');
    });

    if (confirmOk) {
        confirmOk.addEventListener('click', () => {
            const form = pendingForm;
            pendingForm = null;
            closeModal('confirmModal');
            if (!form) return;
            form.dataset.confirmed = '1';
            form.requestSubmit();       // event submit berjalan sinkron
            delete form.dataset.confirmed;
        });
    }

    /* ---------- Tombol loading ---------- */
    window.setLoading = function (btn, on) {
        if (!btn) return;
        btn.classList.toggle('loading', on);
        btn.disabled = on;
    };

    /* ---------- Pencarian: tombol hapus teks ---------- */
    document.querySelectorAll('[data-clear-input]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.clearInput);
            if (input) { input.value = ''; input.focus(); }
        });
    });
})();
