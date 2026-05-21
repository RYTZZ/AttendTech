document.addEventListener('DOMContentLoaded', () => {

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');

    if (sidebarToggle && sidebar) {
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        });
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    updateClock();
    setInterval(updateClock, 1000);

    const notifBtn = document.getElementById('notifBtn');
    if (notifBtn) {
        notifBtn.addEventListener('click', loadNotifications);
    }

    const markAllRead = document.getElementById('markAllRead');
    if (markAllRead) {
        markAllRead.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fetch(BASE_URL + '/ajax/notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_all_read'
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    document.querySelectorAll('.notif-dot, .notif-count').forEach(el => el.remove());
                    loadNotifications();
                }
            });
        });
    }
});

function updateClock() {
    const clock = document.getElementById('topnavClock');
    const dateEl = document.getElementById('topnavDate');
    const now = new Date();
    if (clock) {
        clock.textContent = now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    if (dateEl) {
        dateEl.textContent = now.toLocaleDateString('en-PH', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
    }
}

function loadNotifications() {
    const list = document.getElementById('notifList');
    if (!list) return;
    list.innerHTML = '<div class="notif-loading"><i class="bi bi-arrow-repeat spin"></i></div>';
    fetch(BASE_URL + '/ajax/notifications.php?action=fetch')
        .then(r => r.json())
        .then(data => {
            if (!data.notifications || data.notifications.length === 0) {
                list.innerHTML = '<div class="notif-empty"><i class="bi bi-bell-slash" style="font-size:28px;display:block;margin-bottom:8px;opacity:0.4"></i>No new notifications</div>';
                return;
            }
            list.innerHTML = data.notifications.map(n => {
                const photoHtml = n.photo
                    ? `<img src="${BASE_URL}/${n.photo}" alt="">`
                    : `<i class="bi bi-person-fill"></i>`;
                return `<div class="notif-item ${n.is_read == 0 ? 'unread' : ''}">
                    <div class="notif-avatar">${photoHtml}</div>
                    <div class="notif-content">
                        <div class="notif-msg">${escHtml(n.message)}</div>
                        <div class="notif-time">${timeAgo(n.created_at)}</div>
                    </div>
                </div>`;
            }).join('');
        });
}

function escHtml(str) {
    const el = document.createElement('div');
    el.textContent = str;
    return el.innerHTML;
}

function timeAgo(dateStr) {
    const now = new Date();
    const then = new Date(dateStr);
    const diff = Math.floor((now - then) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff/60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff/3600)}h ago`;
    return then.toLocaleDateString('en-PH');
}

function confirmDelete(url, name) {
    Swal.fire({
        title: 'Delete Student?',
        text: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d93025',
        cancelButtonColor: '#718096',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(url, { method: 'POST' })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: data.message, timer: 1800, showConfirmButton: false })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    }
                });
        }
    });
}
