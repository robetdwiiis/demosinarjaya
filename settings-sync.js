/**
 * Settings Sync Utility
 * Menghubungkan data dari Admin Panel (LocalStorage) ke tampilan Website Utama
 */

document.addEventListener('DOMContentLoaded', () => {
    syncCompanyProfile();
    syncWebsiteSettings();
});

/**
 * Sinkronisasi Profil Perusahaan (Visi, Misi, Sejarah, dkk)
 */
function syncCompanyProfile() {
    const data = JSON.parse(localStorage.getItem('profileSettings'));
    if (!data) return;

    // Update Visi
    updateElementText('about-vision', data['company-vision']);

    // Update Misi (Misi usually in list format)
    const missionContainer = document.getElementById('about-mission');
    if (missionContainer && data['company-mission']) {
        const missions = data['company-mission'].split('\n').filter(line => line.trim() !== '');
        missionContainer.innerHTML = missions.map(m => `
            <li style="padding: 0.5rem 0; display: flex; align-items: flex-start; gap: 0.75rem;">
                <i class="fas fa-check" style="color: var(--primary); margin-top: 0.25rem;"></i>
                <span>${m.replace(/^\d+\.\s*/, '')}</span>
            </li>
        `).join('');
    }

    // Update Company Description / History
    updateElementText('about-history', data['company-description']);
    updateElementText('footer-company-desc', data['company-tagline']);

    // Update Stats
    if (data['company-year']) {
        const currentYear = new Date().getFullYear();
        const experience = currentYear - parseInt(data['company-year']);
        updateElementText('stat-experience', experience + '+');
        updateElementText('about-founded-year', data['company-year']);
    }

    updateElementText('stat-employees', data['company-employees']);

    // Update Global Info
    updateElementText('company-whatsapp-text', data['company-whatsapp']);
    updateElementText('company-phone-text', data['company-phone'] || data['company-whatsapp']);
    updateElementText('company-email-text', data['company-email']);

    // Handle Address with line breaks if any
    const addressEl = document.getElementById('company-address-text');
    if (addressEl && data['company-address']) {
        addressEl.innerText = data['company-address'];
    }

    // Update contact textual labels
    document.querySelectorAll('.company-phone-label').forEach(el => el.innerText = data['company-phone'] || data['company-whatsapp']);
    document.querySelectorAll('.company-email-label').forEach(el => el.innerText = data['company-email']);

    // Update Phone/Email links
    if (data['company-whatsapp']) {
        const waNum = data['company-whatsapp'].replace(/\D/g, '');
        const waLink = `https://wa.me/${waNum}`;
        document.querySelectorAll('.link-whatsapp').forEach(el => el.href = waLink);
    }
    if (data['company-phone']) {
        const phoneNum = data['company-phone'].replace(/\D/g, '');
        document.querySelectorAll('.link-phone').forEach(el => el.href = `tel:${phoneNum}`);
    }
    if (data['company-email']) {
        const emailLink = `mailto:${data['company-email']}`;
        document.querySelectorAll('.link-email').forEach(el => el.href = emailLink);
    }

    // Sync Logo/Banner if elements exist
    const logo = localStorage.getItem('uploaded_logo');
    if (logo) {
        document.querySelectorAll('.website-logo-img').forEach(el => el.src = logo);
    }
    const banner = localStorage.getItem('uploaded_banner');
    if (banner) {
        const hero = document.querySelector('.hero');
        if (hero) hero.style.backgroundImage = `url('${banner}')`;
    }
}

/**
 * Sinkronisasi Pengaturan Website (Social Media)
 */
function syncWebsiteSettings() {
    const data = JSON.parse(localStorage.getItem('websiteSettings'));
    if (!data) return;

    // Update Social Media Links
    updateSocialLink('link-facebook', data['social-facebook']);
    updateSocialLink('link-instagram', data['social-instagram']);
    updateSocialLink('link-shopee', data['social-shopee']);
    updateSocialLink('link-tiktok', data['social-tiktok']);
    updateSocialLink('link-whatsapp', data['social-whatsapp']);
}

/**
 * Helper: Update Text Content
 */
function updateElementText(id, value) {
    if (!value) return;
    const el = document.getElementById(id);
    if (el) el.innerText = value;
}

/**
 * Helper: Update Social Media Link
 */
function updateSocialLink(id, url) {
    if (!url) return;
    const el = document.getElementById(id);
    if (el) {
        if (el.tagName === 'A') {
            el.href = url;
        }
        el.style.display = 'inline-flex';
    }
}
