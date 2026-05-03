/**
 * NABH IMS — Global dynamic app script
 * Loaded on every page. Provides:
 *  - Page fade-in
 *  - Animated counter numbers
 *  - Real-time clock
 *  - Auto-polling notification badge
 *  - Live table search
 *  - Improved toast system
 */

/* ── Page fade-in ── */
document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('page-loaded');
});

/* ── Toast ── */
window.showToast = function(message, type = 'info', duration = 3500) {
    const icons = {
        success: '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        error:   '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        warning: '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        info:    '<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    };
    const container = document.getElementById('toast-container');
    if (!container) return;
    const div = document.createElement('div');
    div.className = `toast ${type}`;
    div.innerHTML = `<div class="flex items-center gap-2">${icons[type] || icons.info}<span>${message}</span></div>`;
    container.appendChild(div);
    setTimeout(() => {
        div.style.animation = 'fadeOut 0.3s ease-in forwards';
        setTimeout(() => div.remove(), 300);
    }, duration);
};

/* ── Animated counter ── */
window.animateCounter = function(el, target, duration = 800) {
    if (!el) return;
    const start = parseInt(el.textContent) || 0;
    if (start === target) return;
    const startTime = performance.now();
    function update(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        // Ease-out cubic
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(start + (target - start) * eased);
        if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
};

/* ── Real-time clock ── */
window.startClock = function(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    function tick() {
        const now = new Date();
        const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        const day  = days[now.getDay()];
        const date = now.getDate().toString().padStart(2,'0');
        const mon  = months[now.getMonth()];
        const yr   = now.getFullYear();
        const h    = now.getHours().toString().padStart(2,'0');
        const m    = now.getMinutes().toString().padStart(2,'0');
        const s    = now.getSeconds().toString().padStart(2,'0');
        el.innerHTML = `<span class="font-medium">${day}, ${date} ${mon} ${yr}</span>
            <span class="clock-time text-blue-700 font-mono font-bold tabular-nums">${h}:${m}:${s}</span>`;
    }
    tick();
    setInterval(tick, 1000);
};

/* ── Live table search ── */
window.liveSearch = function(inputId, tableId, colIndexes) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    input.addEventListener('input', () => {
        const q = input.value.toLowerCase().trim();
        const rows = table.querySelectorAll('tbody tr');
        let visible = 0;
        rows.forEach(row => {
            const cols = colIndexes || [...Array(row.cells.length).keys()];
            const text = cols.map(i => row.cells[i] ? row.cells[i].textContent : '').join(' ').toLowerCase();
            const show = !q || text.includes(q);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        // Show "no results" row
        let noResult = table.querySelector('.no-result-row');
        if (!visible && q) {
            if (!noResult) {
                noResult = document.createElement('tr');
                noResult.className = 'no-result-row';
                const colSpan = table.querySelector('thead tr') ? table.querySelector('thead tr').cells.length : 6;
                noResult.innerHTML = `<td colspan="${colSpan}" class="text-center py-8 text-gray-400">No results match "<strong>${q}</strong>"</td>`;
                table.querySelector('tbody').appendChild(noResult);
            } else {
                noResult.style.display = '';
                noResult.querySelector('td').innerHTML = `No results match "<strong>${q}</strong>"`;
            }
        } else if (noResult) {
            noResult.style.display = 'none';
        }
    });
};

/* ── Notification badge auto-poll ── */
(function startNotifPoll() {
    const badge = document.getElementById('notif-badge');
    if (!badge) return;
    const BASE = window.APP_BASE || '';
    function poll() {
        fetch(`${BASE}/ajax/notifications.php?action=unread_count`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) return;
                const count = d.data.count || 0;
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(() => {});
    }
    // Poll every 30 seconds
    setInterval(poll, 30000);
})();

/* ── Skeleton loader helper ── */
window.showSkeleton = function(containerId, rows = 4, cols = 4) {
    const el = document.getElementById(containerId);
    if (!el) return;
    const cells = Array(cols).fill('<td><div class="skeleton h-4 rounded w-full"></div></td>').join('');
    el.innerHTML = Array(rows).fill(`<tr>${cells}</tr>`).join('');
};

/* ── Card entrance animation on scroll ── */
(function observeCards() {
    if (!('IntersectionObserver' in window)) return;
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('card-visible');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.08 });
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.bg-white.rounded-xl').forEach(card => {
            card.classList.add('card-animate');
            observer.observe(card);
        });
    });
})();
