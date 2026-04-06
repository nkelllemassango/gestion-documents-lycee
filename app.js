/* GestDoc — Main JS */
/* global BASE_URL injected by PHP */

// ── SIDEBAR TOGGLE ──────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Restore collapsed state
    if (localStorage.getItem('sb_collapsed') === '1') document.body.classList.add('sb-collapsed');

    document.getElementById('sidebarToggle')?.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            document.querySelector('.sidebar')?.classList.toggle('mobile-open');
        } else {
            document.body.classList.toggle('sb-collapsed');
            localStorage.setItem('sb_collapsed', document.body.classList.contains('sb-collapsed') ? '1' : '0');
        }
    });

    // Close mobile sidebar on outside click
    document.addEventListener('click', e => {
        const sb = document.querySelector('.sidebar');
        if (sb?.classList.contains('mobile-open') && !sb.contains(e.target) && !document.getElementById('sidebarToggle')?.contains(e.target)) {
            sb.classList.remove('mobile-open');
        }
    });

    // ── DROPDOWNS ──────────────────────────────────────────
    document.addEventListener('click', e => {
        // Notif
        const nb = document.getElementById('notifBtn');
        const nd = document.getElementById('notifDropdown');
        if (nb?.contains(e.target)) {
            nd?.classList.toggle('open');
            document.getElementById('userDropdown')?.classList.remove('open');
            if (nd?.classList.contains('open')) loadNotifs();
        } else if (nd && !nd.contains(e.target)) nd.classList.remove('open');

        // User
        const ub = document.getElementById('userMenuBtn');
        const ud = document.getElementById('userDropdown');
        if (ub?.contains(e.target)) {
            ud?.classList.toggle('open');
            document.getElementById('notifDropdown')?.classList.remove('open');
        } else if (ud && !ud.contains(e.target)) ud.classList.remove('open');
    });

    // ── ACTIVE NAV LINK ─────────────────────────────────────
    const cur = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(a => {
        const href = a.getAttribute('href') || '';
        if (href && cur.endsWith(href.split('/').pop())) a.classList.add('active');
    });

    // ── AUTO-DISMISS ALERTS ──────────────────────────────────
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(a => {
            a.style.transition = 'opacity .5s'; a.style.opacity = '0';
            setTimeout(() => a.remove(), 500);
        });
    }, 5000);

    // ── UPLOAD ZONE ──────────────────────────────────────────
    document.querySelectorAll('.upload-zone').forEach(zone => {
        const inp = zone.querySelector('input[type=file]');
        zone.addEventListener('click', () => inp?.click());
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', e => {
            e.preventDefault(); zone.classList.remove('dragover');
            if (inp && e.dataTransfer.files[0]) {
                const dt = new DataTransfer(); dt.items.add(e.dataTransfer.files[0]);
                inp.files = dt.files; updateFileLabel(zone, e.dataTransfer.files[0].name);
            }
        });
        inp?.addEventListener('change', () => { if (inp.files[0]) updateFileLabel(zone, inp.files[0].name); });
    });

    // ── LIVE SEARCH ──────────────────────────────────────────
    document.querySelectorAll('[data-search]').forEach(inp => {
        const tblId = inp.dataset.search;
        inp.addEventListener('input', () => {
            const q = inp.value.toLowerCase();
            document.querySelectorAll(`#${tblId} tbody tr`).forEach(r => {
                r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    });
});

// ── NOTIFICATIONS AJAX ───────────────────────────────────────
function loadNotifs() {
    fetch(BASE_URL + '/controllers/NotificationController.php?action=get')
        .then(r => r.json()).then(d => {
            const list = document.getElementById('notifList');
            const badge = document.getElementById('notifBadge');
            if (!list) return;
            if (d.items?.length) {
                list.innerHTML = d.items.map(n => `
                <div class="notif-item ${n.statut==='non lu'?'unread':''}" onclick="markRead(${n.id_notification},this)">
                    <div class="notif-icon"><i class="fas fa-bell"></i></div>
                    <div class="notif-text"><p>${esc(n.contenu)}</p><small>${timeAgo(n.date_notification)}</small></div>
                </div>`).join('');
            } else {
                list.innerHTML = '<div class="empty-state" style="padding:20px"><i class="fas fa-bell-slash"></i><p>Aucune notification</p></div>';
            }
            if (badge) { badge.textContent = d.unread; badge.style.display = d.unread > 0 ? 'flex' : 'none'; }
        }).catch(() => {});
}

function markRead(id, el) {
    fetch(BASE_URL + '/controllers/NotificationController.php?action=markRead', {
        method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'id='+id
    }).then(() => { el?.classList.remove('unread'); loadNotifs(); });
}

function markAllRead() {
    fetch(BASE_URL + '/controllers/NotificationController.php?action=markAll', { method: 'POST' })
        .then(() => loadNotifs());
}

// Poll badge every 30s
setInterval(() => {
    if (typeof BASE_URL === 'undefined') return;
    fetch(BASE_URL + '/controllers/NotificationController.php?action=count')
        .then(r => r.json()).then(d => {
            const b = document.getElementById('notifBadge');
            if (b) { b.textContent = d.count; b.style.display = d.count > 0 ? 'flex' : 'none'; }
        }).catch(() => {});
}, 30000);

// ── TOAST ────────────────────────────────────────────────────
function showToast(msg, type = 'info', dur = 3500) {
    const wrap = document.getElementById('toastWrap') || (() => {
        const d = document.createElement('div'); d.id = 'toastWrap'; d.className = 'toast-wrap';
        document.body.appendChild(d); return d;
    })();
    const colors = { success:'#22c55e', error:'#ef4444', warn:'#eab308', info:'#3b82f6' };
    const icons  = { success:'check-circle', error:'times-circle', warn:'exclamation-triangle', info:'info-circle' };
    const t = document.createElement('div');
    t.className = 'toast'; t.style.borderLeftColor = colors[type] || colors.info;
    t.innerHTML = `<i class="fas fa-${icons[type]||'info-circle'}" style="color:${colors[type]}"></i>
                   <span>${esc(msg)}</span>
                   <button class="t-close" onclick="this.parentElement.remove()">×</button>`;
    wrap.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .4s'; setTimeout(() => t.remove(), 400); }, dur);
}

// ── CONFIRM DELETE ───────────────────────────────────────────
function confirmDel(url, msg = 'Confirmer la suppression ?') {
    if (confirm(msg)) window.location.href = url;
}

// ── WEBCAM ───────────────────────────────────────────────────
let _stream = null;
function startCam(videoId) {
    navigator.mediaDevices.getUserMedia({ video: true }).then(s => {
        _stream = s;
        const v = document.getElementById(videoId);
        if (v) { v.srcObject = s; v.play(); }
    }).catch(e => showToast('Caméra inaccessible: ' + e.message, 'error'));
}
function stopCam() { _stream?.getTracks().forEach(t => t.stop()); _stream = null; }
function captureCam(vid, cvs, inp) {
    const v = document.getElementById(vid), c = document.getElementById(cvs);
    if (!v || !c) return;
    c.width = v.videoWidth; c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0);
    document.getElementById(inp).value = c.toDataURL('image/jpeg', .9);
    stopCam(); showToast('Photo capturée !', 'success');
}

// ── UTILS ────────────────────────────────────────────────────
function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function timeAgo(d) {
    const s = Math.floor((Date.now() - new Date(d)) / 1000);
    if (s < 60) return 'À l\'instant'; if (s < 3600) return Math.floor(s/60)+'min';
    if (s < 86400) return Math.floor(s/3600)+'h'; return Math.floor(s/86400)+'j';
}
function updateFileLabel(zone, name) {
    const p = zone.querySelector('p'); if (p) p.innerHTML = `<i class="fas fa-file" style="margin-right:5px;color:var(--a)"></i>${esc(name)}`;
}
