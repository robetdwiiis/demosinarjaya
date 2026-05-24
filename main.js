// Main Frontend JavaScript
document.addEventListener('DOMContentLoaded', function () {
    // Initialize components
    initNavigation();
    initScrollEffects();
    initAnimations();
    loadTestimonials();
    initTestimonialForm();
});

// Navigation
function initNavigation() {
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            navToggle.classList.toggle('active');
        });

        // Close menu on link click
        navMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                navToggle.classList.remove('active');
            });
        });
    }

    // Set active nav link
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.nav-menu a').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage || (currentPage === '' && href === 'index.html')) {
            link.classList.add('active');
        }
    });
}

// Scroll Effects
function initScrollEffects() {
    const header = document.querySelector('.header');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header?.classList.add('scrolled');
        } else {
            header?.classList.remove('scrolled');
        }
    });
}

// Scroll Animations
function initAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right').forEach(el => {
        observer.observe(el);
    });
}

// Load Testimonials from Database
async function loadTestimonials() {
    const container = document.getElementById('testimonial-container');
    if (!container) return;

    try {
        const response = await fetch('api/testimonials.php');
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            container.innerHTML = result.data.map(testi => {
                // Generate stars
                let stars = '';
                for (let i = 1; i <= 5; i++) {
                    stars += `<i class="${i <= testi.rating ? 'fas' : 'far'} fa-star"></i>`;
                }

                return `
                    <div class="testimonial-card fade-in visible">
                        <div class="testimonial-rating">
                            ${stars}
                        </div>
                        <p class="testimonial-text">
                            "${testi.testimoni}"
                        </p>
                        <div class="testimonial-author">
                            <img src="${testi.foto || 'https://i.pravatar.cc/100?u=' + testi.nama}" alt="${testi.nama}">
                            <div class="testimonial-author-info">
                                <strong>${testi.nama}</strong>
                                <span>${testi.perusahaan}${testi.jabatan ? ' - ' + testi.jabatan : ''}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    } catch (error) {
        console.error('Error loading testimonials:', error);
    }
}

// Counter Animation
function animateCounter(element, target, duration = 2000) {
    let startTime = null;
    const initialText = element.textContent;
    const hasPlus = initialText.includes('+');

    function update(currentTime) {
        if (!startTime) startTime = currentTime;
        const progress = Math.min((currentTime - startTime) / duration, 1);

        // Easing function (outQuart)
        const easeProgress = 1 - Math.pow(1 - progress, 4);
        const currentCount = Math.floor(easeProgress * target);

        element.textContent = currentCount + (hasPlus ? '+' : '');

        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            element.textContent = target + (hasPlus ? '+' : '');
        }
    }

    requestAnimationFrame(update);
}

// Initialize counters when in view
function initCounters() {
    const counters = document.querySelectorAll('[data-counter]');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = parseInt(entry.target.dataset.counter);
                animateCounter(entry.target, target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    counters.forEach(counter => observer.observe(counter));
}

// Handle Testimonial Form Submission
function initTestimonialForm() {
    const form = document.getElementById('form-testimonial');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('api/testimonials.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                form.reset();
                closeModal('modal-testimonial');
            } else {
                showToast(result.message || 'Gagal mengirim ulasan', 'error');
            }
        } catch (error) {
            console.error('Error submitting testimonial:', error);
            showToast('Terjadi kesalahan koneksi', 'error');
        }
    });
}

// Lightbox
function initLightbox() {
    const galleryItems = document.querySelectorAll('.gallery-item');
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    let currentIndex = 0;

    if (!lightbox || !galleryItems.length) return;

    galleryItems.forEach((item, index) => {
        item.addEventListener('click', () => {
            currentIndex = index;
            const img = item.querySelector('img');
            lightboxImg.src = img.src;
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    document.querySelector('.lightbox-close')?.addEventListener('click', closeLightbox);
    document.querySelector('.lightbox-prev')?.addEventListener('click', () => navigate(-1));
    document.querySelector('.lightbox-next')?.addEventListener('click', () => navigate(1));

    lightbox?.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox();
    });

    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    function navigate(direction) {
        currentIndex = (currentIndex + direction + galleryItems.length) % galleryItems.length;
        const img = galleryItems[currentIndex].querySelector('img');
        lightboxImg.src = img.src;
    }

    document.addEventListener('keydown', (e) => {
        if (!lightbox.classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') navigate(-1);
        if (e.key === 'ArrowRight') navigate(1);
    });
}

// Modal
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Close modal on backdrop click
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

// Form Validation
function validateForm(formElement) {
    let isValid = true;
    const inputs = formElement.querySelectorAll('[required]');

    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('error');
            showFieldError(input, 'Field ini wajib diisi');
        } else {
            input.classList.remove('error');
            removeFieldError(input);
        }

        // Email validation
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                isValid = false;
                input.classList.add('error');
                showFieldError(input, 'Format email tidak valid');
            }
        }
    });

    return isValid;
}

function showFieldError(input, message) {
    removeFieldError(input);
    const error = document.createElement('span');
    error.className = 'field-error';
    error.style.cssText = 'color: var(--error); font-size: 0.75rem; display: block; margin-top: 0.25rem;';
    error.textContent = message;
    input.parentNode.appendChild(error);
}

function removeFieldError(input) {
    const error = input.parentNode.querySelector('.field-error');
    if (error) error.remove();
}

// Smooth scroll to element
function scrollToElement(selector) {
    const element = document.querySelector(selector);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Export functions
window.initLightbox = initLightbox;
window.openModal = openModal;
window.closeModal = closeModal;
window.validateForm = validateForm;
window.scrollToElement = scrollToElement;
window.initCounters = initCounters;

// ==================== AUTO-UPDATE FROM ADMIN SETTINGS ====================

// Get settings from localStorage (same keys used in admin/settings.html)
function getAdminSettings(key) {
    try {
        const data = localStorage.getItem(`settings_${key}`);
        return data ? JSON.parse(data) : null;
    } catch (e) {
        console.error('Get admin settings error:', e);
        return null;
    }
}

// Update footer social media links from admin settings
function updateSocialLinksFromSettings() {
    const websiteSettings = getAdminSettings('website');

    if (!websiteSettings) return;

    // Find footer social links
    const footerSocial = document.querySelector('.footer-social');
    if (!footerSocial) return;

    // Update Facebook link
    if (websiteSettings.facebook) {
        const fbLink = footerSocial.querySelector('a[href*="facebook"]');
        if (fbLink) fbLink.href = websiteSettings.facebook;
    }

    // Update Instagram link
    if (websiteSettings.instagram) {
        const igLink = footerSocial.querySelector('a[href*="instagram"]');
        if (igLink) igLink.href = websiteSettings.instagram;
    }

    // Update Shopee link
    if (websiteSettings.shopee) {
        const shopeeLink = footerSocial.querySelector('a[href*="shopee"]');
        if (shopeeLink) shopeeLink.href = websiteSettings.shopee;
    }

    console.log('Social links updated from admin settings!');
}

// Update contact info from admin settings
function updateContactFromSettings() {
    const companySettings = getAdminSettings('company');

    if (!companySettings) return;

    // Update WhatsApp links
    if (companySettings.whatsapp) {
        const waNumber = companySettings.whatsapp.replace(/\D/g, '');

        // Update all WhatsApp links on the page
        document.querySelectorAll('a[href*="wa.me"]').forEach(link => {
            link.href = `https://wa.me/${waNumber}`;
        });

        // Update WhatsApp text in footer
        const waText = document.querySelector('.footer-contact-item .fa-whatsapp')?.parentElement?.querySelector('span');
        if (waText) waText.textContent = companySettings.whatsapp;
    }

    // Update Email
    if (companySettings.email) {
        const emailText = document.querySelector('.footer-contact-item .fa-envelope')?.parentElement?.querySelector('span');
        if (emailText) emailText.textContent = companySettings.email;
    }

    // Update Address
    if (companySettings.address) {
        const addressText = document.querySelector('.footer-contact-item .fa-map-marker-alt')?.parentElement?.querySelector('span');
        if (addressText) addressText.textContent = companySettings.address;
    }

    console.log('Contact info updated from admin settings!');
}

// ==================== DATABASE API INTEGRATION ====================

// Load settings from database API
async function loadSettingsFromDatabase() {
    try {
        const response = await fetch('api/settings.php');
        const result = await response.json();

        if (result.success && result.data) {
            const data = result.data;

            // Update footer social links from database
            const footerSocial = document.querySelector('.footer-social');
            if (footerSocial && data.social) {
                if (data.social.facebook) {
                    const fbLink = footerSocial.querySelector('a[href*="facebook"]');
                    if (fbLink) fbLink.href = data.social.facebook;
                }
                if (data.social.instagram) {
                    const igLink = footerSocial.querySelector('a[href*="instagram"]');
                    if (igLink) igLink.href = data.social.instagram;
                }
                if (data.social.shopee) {
                    const shopeeLink = footerSocial.querySelector('a[href*="shopee"]');
                    if (shopeeLink) shopeeLink.href = data.social.shopee;
                }
            }

            // Update contact info from database
            if (data.contact) {
                // Update WhatsApp
                if (data.contact.whatsapp) {
                    const waNumber = data.contact.whatsapp.replace(/\D/g, '');
                    document.querySelectorAll('a[href*="wa.me"]').forEach(link => {
                        link.href = `https://wa.me/${waNumber}`;
                    });
                    const waText = document.querySelector('.footer-contact-item .fa-whatsapp')?.parentElement?.querySelector('span');
                    if (waText) waText.textContent = data.contact.telepon || data.contact.whatsapp;
                }

                // Update Email
                if (data.contact.email) {
                    const emailText = document.querySelector('.footer-contact-item .fa-envelope')?.parentElement?.querySelector('span');
                    if (emailText) emailText.textContent = data.contact.email;
                }

                // Update Address
                if (data.contact.alamat) {
                    const addressText = document.querySelector('.footer-contact-item .fa-map-marker-alt')?.parentElement?.querySelector('span');
                    if (addressText) addressText.textContent = data.contact.alamat;
                }
            }

            console.log('Settings loaded from database!');
            return true;
        }
    } catch (error) {
        console.log('Database not available, using localStorage fallback');
    }
    return false;
}

// Initialize settings sync on page load
async function initSettingsSync() {
    // Try to load from database first
    const dbLoaded = await loadSettingsFromDatabase();

    // If database failed, fallback to localStorage
    if (!dbLoaded) {
        updateSocialLinksFromSettings();
        updateContactFromSettings();
    }
}

// Run settings sync when DOM is ready
document.addEventListener('DOMContentLoaded', initSettingsSync);

// Export sync function
window.initSettingsSync = initSettingsSync;
window.getAdminSettings = getAdminSettings;
window.loadSettingsFromDatabase = loadSettingsFromDatabase;

