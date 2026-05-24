/**
 * Notification System - Admin Dashboard
 * SINAR JAYA KONVEKSI
 */

// API Endpoint
const NOTIFICATION_API = '../api/dashboard.php';
const MESSAGES_API = '../api/contact.php';

// Notification State
let notificationState = {
    unreadMessages: 0,
    lowStockCount: 0,
    notifications: []
};

// Initialize notifications on page load
document.addEventListener('DOMContentLoaded', function () {
    initNotificationSystem();
});

/**
 * Initialize the notification system
 */
function initNotificationSystem() {
    // Setup dropdown toggle
    const notificationBtn = document.getElementById('notification-btn');
    const notificationDropdown = document.getElementById('notification-dropdown');

    if (notificationBtn && notificationDropdown) {
        notificationBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationDropdown.classList.toggle('active');
            if (notificationDropdown.classList.contains('active')) {
                loadNotifications();
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!notificationDropdown.contains(e.target) && !notificationBtn.contains(e.target)) {
                notificationDropdown.classList.remove('active');
            }
        });
    }

    // Load initial notification count
    loadNotificationCount();

    // Auto refresh every 60 seconds
    setInterval(loadNotificationCount, 60000);
}

/**
 * Load notification count (badge)
 */
async function loadNotificationCount() {
    try {
        const response = await fetch(`${NOTIFICATION_API}?action=summary`);
        const data = await response.json();

        if (data.success) {
            const unreadMessages = data.data.unread_messages || 0;
            const lowStockCount = data.data.low_stock_count || 0;
            const totalCount = unreadMessages + lowStockCount;

            notificationState.unreadMessages = unreadMessages;
            notificationState.lowStockCount = lowStockCount;

            updateBadge(totalCount);
        }
    } catch (error) {
        console.error('Failed to load notification count:', error);
    }
}

/**
 * Update the badge number
 */
function updateBadge(count) {
    const badge = document.getElementById('notification-badge');
    const btn = document.getElementById('notification-btn');

    if (!badge) return;

    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.classList.add('has-count');
        badge.style.display = 'flex';

        // Add shake animation class
        if (btn) btn.classList.add('has-notifications');
    } else {
        badge.textContent = '';
        badge.classList.remove('has-count');
        badge.style.display = 'none';

        // Remove shake animation class
        if (btn) btn.classList.remove('has-notifications');
    }
}

/**
 * Load notifications for dropdown
 */
async function loadNotifications() {
    const listContainer = document.getElementById('notification-list');
    if (!listContainer) return;

    // Show loading
    // Show loading
    listContainer.innerHTML = `
        <div class="notification-loading">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Memuat notifikasi...</p>
        </div>
    `;

    try {
        // Fetch messages and stock alerts in parallel
        const [messagesResponse, summaryResponse] = await Promise.all([
            fetch(`${MESSAGES_API}?status=unread&limit=5`),
            fetch(`${NOTIFICATION_API}?action=summary`)
        ]);

        const messagesData = await messagesResponse.json();
        const summaryData = await summaryResponse.json();

        let notifications = [];

        // Add unread messages
        if (messagesData.success && messagesData.data && messagesData.data.length > 0) {
            messagesData.data.forEach(msg => {
                notifications.push({
                    type: 'message',
                    icon: 'envelope',
                    iconClass: 'message',
                    title: `Pesan dari ${msg.nama}`,
                    description: msg.pesan.substring(0, 80) + (msg.pesan.length > 80 ? '...' : ''),
                    time: formatTimeAgo(msg.created_at),
                    link: 'messages.html',
                    unread: msg.status === 'unread'
                });
            });
        }

        // Add low stock alerts
        if (summaryData.success && summaryData.data.low_stock_count > 0) {
            notifications.push({
                type: 'warning',
                icon: 'exclamation-triangle',
                iconClass: 'warning',
                title: 'Stok Menipis',
                description: `${summaryData.data.low_stock_count} produk dengan stok di bawah 50 unit`,
                time: 'Sekarang',
                link: 'products.html?filter=low-stock',
                unread: true
            });
        }

        // Render notifications
        renderNotifications(notifications);

    } catch (error) {
        console.error('Failed to load notifications:', error);
        listContainer.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-exclamation-circle"></i>
                <p>Gagal memuat notifikasi</p>
            </div>
        `;
    }
}

/**
 * Render notifications to the dropdown
 */
function renderNotifications(notifications) {
    const listContainer = document.getElementById('notification-list');
    if (!listContainer) return;

    if (notifications.length === 0) {
        listContainer.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <p>Tidak ada notifikasi baru</p>
            </div>
        `;
        return;
    }

    listContainer.innerHTML = notifications.map(notif => `
        <div class="notification-item ${notif.unread ? 'unread' : ''}" onclick="handleNotificationClick('${notif.link}')">
            <div class="notification-icon ${notif.iconClass}">
                <i class="fas fa-${notif.icon}"></i>
            </div>
            <div class="notification-content">
                <strong>${escapeHtml(notif.title)}</strong>
                <p>${escapeHtml(notif.description)}</p>
                <div class="notification-time">
                    <i class="fas fa-clock"></i> ${notif.time}
                </div>
            </div>
        </div>
    `).join('');
}

/**
 * Handle notification item click
 */
function handleNotificationClick(link) {
    window.location.href = link;
}

/**
 * Mark all notifications as read
 */
async function markAllRead() {
    try {
        // Mark all messages as read
        await fetch(MESSAGES_API, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_all_read' })
        });

        // Reload notifications
        loadNotificationCount();
        loadNotifications();

    } catch (error) {
        console.error('Failed to mark all as read:', error);
    }
}

/**
 * Format time ago
 */
function formatTimeAgo(dateString) {
    if (!dateString) return 'Baru saja';

    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'Baru saja';
    if (seconds < 3600) return `${Math.floor(seconds / 60)} menit lalu`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)} jam lalu`;
    if (seconds < 604800) return `${Math.floor(seconds / 86400)} hari lalu`;

    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
    });
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export functions
window.loadNotifications = loadNotifications;
window.loadNotificationCount = loadNotificationCount;
window.markAllRead = markAllRead;
window.handleNotificationClick = handleNotificationClick;
