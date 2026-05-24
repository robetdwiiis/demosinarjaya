/**
 * Authentication Guard & Session Management
 * Proteksi halaman admin agar tidak bisa diakses tanpa login.
 */

// Konfigurasi Auth
const AUTH_API = '../api/auth.php';
const LOGIN_PAGE = 'login.html';

// Cek status login saat halaman dimuat
document.addEventListener('DOMContentLoaded', async () => {
    // Jangan cek di halaman login itu sendiri
    if (window.location.pathname.endsWith('login.html')) return;

    try {
        const response = await fetch(`${AUTH_API}?action=check&_t=${new Date().getTime()}`);
        const data = await response.json();

        if (!data.is_logged_in) {
            // Jika belum login, redirect ke halaman login
            console.warn('Unauthorized access. Redirecting to login...');
            window.location.href = LOGIN_PAGE;
        } else {
            // Jika sudah login, update UI (opsional)
            updateUserInfo(data);
        }

    } catch (error) {
        console.error('Auth check failed:', error);
        // FORCE REDIRECT ON ERROR (Fail-Safe)
        // Jika server error atau tidak bisa dihubungi, anggap tidak aman -> login ulang
        window.location.href = LOGIN_PAGE;
    }
});


// Fungsi Logout
window.logout = async function () {
    if (!confirm('Apakah Anda yakin ingin keluar?')) return;

    try {
        const response = await fetch(`${AUTH_API}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        });

        const data = await response.json();

        if (data.success) {
            // Optional: clear local storage if any
            localStorage.removeItem('active_admin_session');
            window.location.href = LOGIN_PAGE;
        }

    } catch (error) {
        console.error('Logout failed:', error);
        alert('Gagal logout. Coba lagi.');
    }
};


// Update info user di sidebar/header
function updateUserInfo(userData) {
    // Cari elemen nama user di sidebar
    const userNameEl = document.querySelector('.user-details strong');
    const userRoleEl = document.querySelector('.user-details span');

    if (userNameEl && userData.username) {
        userNameEl.textContent = userData.username; // atau full_name
    }

    if (userRoleEl && userData.role) {
        userRoleEl.textContent = userData.role === 'super_admin' ? 'Super Admin' : 'Admin';
    }
}
