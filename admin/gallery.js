// Admin Gallery CRUD
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('gallery-grid')) {
        initGalleryAdmin();
    }
});

let editingGalleryId = null;
let uploadedGalleryImage = null; // Store base64

function initGalleryAdmin() {
    loadGalleryGrid();
    initGalleryForm();
    initGalleryImageUpload();
}

// Helper to get gallery data (LocalStorage > Defaults)
function getGalleryData() {
    const stored = Storage.get('gallery');
    // If valid array (even empty), use it. If null (not set), use defaults.
    if (Array.isArray(stored)) {
        return stored;
    }
    // Fallback to SinarJayaData if available
    if (SinarJayaData.gallery && Array.isArray(SinarJayaData.gallery)) {
        return JSON.parse(JSON.stringify(SinarJayaData.gallery));
    }
    return [];
}

// Load Gallery Grid
function loadGalleryGrid() {
    const grid = document.getElementById('gallery-grid');
    const emptyState = document.getElementById('gallery-empty-state');
    if (!grid) return;

    // Get current gallery
    let gallery = getGalleryData();

    // Safety check if something went wrong with storage
    if (!Array.isArray(gallery)) gallery = [];

    const filterCategory = document.getElementById('filter-category')?.value || 'all';

    let filtered = gallery;
    if (filterCategory !== 'all') {
        filtered = filtered.filter(item => item.category === filterCategory);
    }

    if (filtered.length === 0) {
        grid.innerHTML = '';
        if (emptyState) emptyState.style.display = 'block';
        return;
    }

    if (emptyState) emptyState.style.display = 'none';

    grid.innerHTML = filtered.map(item => `
        <div class="gallery-card">
            <div style="position: relative;">
                <img src="${item.image}" alt="${item.title}" class="gallery-card-img">
                <div class="gallery-card-actions">
                    <button class="action-btn edit" onclick="editGallery(${item.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn delete" onclick="deleteGallery(${item.id})" title="Hapus">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="gallery-card-body">
                <div class="gallery-card-info">
                    <h5>${item.title}</h5>
                    <div class="gallery-card-meta">
                        <span class="badge badge-sm badge-secondary">${item.category}</span>
                        <span>${item.date || ''}</span>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--gray-500); margin-top: 0.5rem; line-height: 1.4;">
                        ${item.description || '-'}
                    </p>
                </div>
            </div>
        </div>
    `).join('');
}

// Form Handling
function initGalleryForm() {
    const form = document.getElementById('gallery-form');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        saveGallery();
    });

    document.getElementById('filter-category')?.addEventListener('change', loadGalleryGrid);
}

function openAddGallery() {
    editingGalleryId = null;
    uploadedGalleryImage = null;
    document.getElementById('modal-title').textContent = 'Tambah Foto Baru';
    document.getElementById('gallery-form').reset();
    resetPreview();
    openModal('gallery-modal');
}

function editGallery(id) {
    const gallery = getGalleryData();
    const item = gallery.find(g => g.id == id); // Use loose equality for string/number id mismatch
    if (!item) {
        console.error('Gallery item not found:', id);
        return;
    }

    editingGalleryId = item.id; // Store exact ID type
    uploadedGalleryImage = null; // Keep existing unless changed

    document.getElementById('modal-title').textContent = 'Edit Foto Galeri';
    document.getElementById('gallery-title').value = item.title;
    document.getElementById('gallery-category').value = item.category;
    document.getElementById('gallery-description').value = item.description || '';

    // Show preview
    updatePreview(item.image);

    openModal('gallery-modal');
}

function saveGallery() {
    let gallery = getGalleryData();

    // Determine Image
    let finalImage = null; // No default if new

    if (uploadedGalleryImage) {
        finalImage = uploadedGalleryImage;
    } else {
        const urlInput = document.getElementById('gallery-image-url')?.value;
        if (urlInput) {
            finalImage = urlInput;
        } else if (editingGalleryId) {
            const existing = gallery.find(g => g.id == editingGalleryId);
            if (existing) finalImage = existing.image;
        }
    }

    // Validation: Image Required
    if (!finalImage) {
        showToast('Foto wajib diisi (Upload atau URL)', 'error');
        return;
    }

    const galleryData = {
        title: document.getElementById('gallery-title').value,
        category: document.getElementById('gallery-category').value,
        description: document.getElementById('gallery-description').value,
        image: finalImage,
        date: new Date().toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
    };

    if (editingGalleryId) {
        galleryData.id = editingGalleryId;
        const index = gallery.findIndex(g => g.id == editingGalleryId);
        if (index !== -1) {
            gallery[index] = { ...gallery[index], ...galleryData };
        }
    } else {
        galleryData.id = Date.now();
        gallery.push(galleryData);
    }

    if (Storage.set('gallery', gallery)) {
        showToast(editingGalleryId ? 'Foto berhasil diperbarui!' : 'Foto berhasil ditambahkan!');
        closeModal('gallery-modal');
        loadGalleryGrid();
    } else {
        showToast('Gagal menyimpan (Storage Penuh/Error)', 'error');
        // If failed, maybe revert the array change if we care, but here we just re-read or let it stay in memory until refresh
    }
}

function deleteGallery(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus foto ini?')) return;

    let gallery = getGalleryData();
    gallery = gallery.filter(g => g.id != id); // Loose equality
    if (Storage.set('gallery', gallery)) {
        showToast('Foto berhasil dihapus', 'info');
        loadGalleryGrid();
    } else {
        showToast('Gagal menghapus (Storage Error)', 'error');
    }
}

// Image Upload Logic with Compression
function initGalleryImageUpload() {
    const input = document.getElementById('gallery-image-input'); // Changed ID
    const urlInput = document.getElementById('gallery-image-url');

    if (input) {
        input.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const file = this.files[0];

                // Read and compress
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = new Image();
                    img.onload = function () {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        // Max dimensions
                        const MAX_WIDTH = 800;
                        const MAX_HEIGHT = 800;
                        let width = img.width;
                        let height = img.height;

                        if (width > height) {
                            if (width > MAX_WIDTH) {
                                height *= MAX_WIDTH / width;
                                width = MAX_WIDTH;
                            }
                        } else {
                            if (height > MAX_HEIGHT) {
                                width *= MAX_HEIGHT / height;
                                height = MAX_HEIGHT;
                            }
                        }

                        canvas.width = width;
                        canvas.height = height;
                        ctx.drawImage(img, 0, 0, width, height);

                        // Compress to JPEG 0.7 quality
                        const dataUrl = canvas.toDataURL('image/jpeg', 0.7);

                        uploadedGalleryImage = dataUrl;
                        if (urlInput) urlInput.value = '';
                        updatePreview(dataUrl);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (urlInput) {
        urlInput.addEventListener('input', function () {
            if (this.value) {
                uploadedGalleryImage = null;
                updatePreview(this.value);
            }
        });
    }
}

function updatePreview(src) {
    const preview = document.getElementById('image-preview');
    if (!preview) return;

    preview.innerHTML = `
        <div style="position: relative; width: 100%; height: 100%;">
            <img src="${src}" alt="Preview" style="width: 100%; height: 100%; object-fit: contain;">
             <button type="button" onclick="resetPreview()" 
                style="position: absolute; top: 10px; right: 10px; background: rgba(239, 68, 68, 0.9); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
}

function resetPreview() {
    uploadedGalleryImage = null;
    const input = document.getElementById('gallery-image-input'); // Changed ID
    if (input) input.value = '';

    const urlInput = document.getElementById('gallery-image-url');
    if (urlInput) urlInput.value = '';

    const preview = document.getElementById('image-preview');
    if (preview) {
        preview.innerHTML = `
            <div class="no-image-placeholder">
                <i class="fas fa-image"></i>
                <span>Preview Gambar</span>
            </div>
        `;
    }
}

// Export functions to window
window.openAddGallery = openAddGallery;
window.editGallery = editGallery;
window.deleteGallery = deleteGallery;
window.resetPreview = resetPreview;
