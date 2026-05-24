/**
 * Settings API Client
 * SINAR JAYA KONVEKSI
 * 
 * Client JavaScript untuk mengelola settings via API
 */

const SettingsAPI = {
    // Base URL for API
    baseURL: '../api/settings.php',

    /**
     * Get all settings
     * @returns {Promise} All settings grouped by category
     */
    async getAll() {
        try {
            const response = await fetch(this.baseURL);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error fetching settings:', error);
            return { success: false, message: error.message };
        }
    },

    /**
     * Get settings by group
     * @param {string} group - Group name (general, contact, social, business, stats)
     * @returns {Promise} Settings for the specified group
     */
    async getByGroup(group) {
        try {
            const response = await fetch(`${this.baseURL}?group=${group}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error(`Error fetching ${group} settings:`, error);
            return { success: false, message: error.message };
        }
    },

    /**
     * Get single setting by key
     * @param {string} key - Setting key
     * @returns {Promise} Single setting value
     */
    async getByKey(key) {
        try {
            const response = await fetch(`${this.baseURL}?key=${key}`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error(`Error fetching setting ${key}:`, error);
            return { success: false, message: error.message };
        }
    },

    /**
     * Update settings
     * @param {Object} settings - Object containing key-value pairs to update
     * @param {string} group - Optional group name for new settings
     * @returns {Promise} Update result
     */
    async update(settings, group = null) {
        try {
            const body = { settings };
            if (group) body.group = group;

            const response = await fetch(this.baseURL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(body)
            });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error updating settings:', error);
            return { success: false, message: error.message };
        }
    },

    /**
     * Update company profile settings
     * @param {Object} profileData - Company profile data
     */
    async updateCompanyProfile(profileData) {
        const settings = {
            nama_perusahaan: profileData.name || '',
            tagline: profileData.tagline || '',
            deskripsi: profileData.description || '',
            tahun_berdiri: profileData.founded || '',
            pendiri: profileData.founders || '',
            jumlah_karyawan: profileData.employees || ''
        };
        return await this.update(settings, 'general');
    },

    /**
     * Update contact settings
     * @param {Object} contactData - Contact data
     */
    async updateContact(contactData) {
        const settings = {
            telepon: contactData.phone || '',
            whatsapp: contactData.whatsapp || '',
            email: contactData.email || '',
            alamat: contactData.address || ''
        };
        return await this.update(settings, 'contact');
    },

    /**
     * Update social media settings
     * @param {Object} socialData - Social media links
     */
    async updateSocialMedia(socialData) {
        const settings = {
            facebook: socialData.facebook || '',
            instagram: socialData.instagram || '',
            shopee: socialData.shopee || '',
            tiktok: socialData.tiktok || '',
            youtube: socialData.youtube || ''
        };
        return await this.update(settings, 'social');
    }
};

// Export for use in other scripts
window.SettingsAPI = SettingsAPI;
