// Admin Products CRUD - Database-First Approach
document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('products-table')) {
    initProductsAdmin();
  }
});

let editingProductId = null;
let uploadedImageBase64 = null;
let cachedProducts = []; // In-memory cache of products from DB

function initProductsAdmin() {
  loadProductsTable();
  initProductForm();
  initImageUpload();
}

// Load Products Table - Always fetch from database API first
async function loadProductsTable() {
  const tbody = document.getElementById('products-table');
  if (!tbody) return;

  // Show loading state
  tbody.innerHTML = `
    <tr>
      <td colspan="6" style="text-align: center; padding: 2rem;">
        <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; color: var(--primary);"></i>
        <p style="color: var(--gray-500); margin-top: 0.5rem;">Memuat produk...</p>
      </td>
    </tr>
  `;

  try {
    // Fetch from database API - include all statuses for admin
    const response = await fetch('../api/products.php?status=active');
    const result = await response.json();

    if (result.success && result.data) {
      cachedProducts = result.data;
    } else {
      throw new Error(result.message || 'Failed to load products');
    }
  } catch (err) {
    console.warn('API tidak tersedia, menggunakan localStorage:', err);
    cachedProducts = Storage.get('products') || SinarJayaData.products || [];
  }

  renderProductsTable(cachedProducts);
}

// Render the products table from given data
function renderProductsTable(products) {
  const tbody = document.getElementById('products-table');
  if (!tbody) return;

  const searchQuery = document.getElementById('search-products')?.value?.toLowerCase() || '';
  const categoryFilter = document.getElementById('filter-category')?.value || 'all';

  let filtered = [...products];

  if (searchQuery) {
    filtered = filtered.filter(p =>
      (p.name || '').toLowerCase().includes(searchQuery) ||
      (p.description || '').toLowerCase().includes(searchQuery)
    );
  }

  if (categoryFilter !== 'all') {
    filtered = filtered.filter(p => p.category === categoryFilter);
  }

  if (filtered.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" style="text-align: center; padding: 2rem;">
          <i class="fas fa-box-open" style="font-size: 2rem; color: var(--gray-300);"></i>
          <p style="color: var(--gray-500); margin-top: 0.5rem;">Tidak ada produk ditemukan</p>
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = filtered.map(product => `
    <tr>
      <td>
        <div class="product-cell">
          <img src="${resolveImageUrl(product.image)}" alt="${product.name}" onerror="this.src='https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400'">
          <div>
            <strong>${product.name}</strong>
            <span style="display: block; font-size: 0.75rem; color: var(--gray-500);">${(product.description || '').substring(0, 40)}${(product.description || '').length > 0 ? '...' : ''}</span>
          </div>
        </div>
      </td>
      <td>${getCategoryName(product.category)}</td>
      <td>${formatCurrency(product.price)}</td>
      <td>
        <span class="status-badge ${product.stock > 50 ? 'active' : product.stock > 20 ? 'low' : 'out'}">
          ${product.stock} pcs
        </span>
      </td>
      <td>${product.featured ? '<i class="fas fa-star" style="color: var(--secondary);"></i>' : '-'}</td>
      <td>
        <div class="actions">
          <button class="edit" onclick="editProduct(${product.id})" title="Edit">
            <i class="fas fa-edit"></i>
          </button>
          <button class="delete" onclick="deleteProduct(${product.id})" title="Hapus">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

function getCategoryName(categoryId) {
  const cat = SinarJayaData.categories.find(c => c.id === categoryId);
  return cat ? cat.name : categoryId;
}

// Product Form
function initProductForm() {
  const form = document.getElementById('product-form');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    saveProduct();
  });

  // Populate category select
  const categorySelect = document.getElementById('product-category');
  if (categorySelect) {
    categorySelect.innerHTML = '<option value="">Pilih Kategori</option>' +
      SinarJayaData.categories.map(cat =>
        `<option value="${cat.id}">${cat.name}</option>`
      ).join('');
  }
}

// Open Add Product Modal
function openAddProduct() {
  editingProductId = null;
  uploadedImageBase64 = null;
  document.getElementById('modal-title').textContent = 'Tambah Produk Baru';
  document.getElementById('product-form').reset();

  const categorySelect = document.getElementById('product-category');
  if (categorySelect) categorySelect.value = '';

  updatePreview(null);
  const urlInput = document.getElementById('product-image-url');
  if (urlInput) urlInput.value = '';
  openModal('product-modal');
}

// Edit Product - Use cached data from database
function editProduct(productId) {
  const product = cachedProducts.find(p => p.id === productId);

  if (!product) {
    showToast('Produk tidak ditemukan!', 'error');
    return;
  }

  editingProductId = productId;
  uploadedImageBase64 = null;
  document.getElementById('modal-title').textContent = 'Edit Produk';

  document.getElementById('product-name').value = product.name || '';
  document.getElementById('product-category').value = product.category || '';
  document.getElementById('product-price').value = product.price || 0;
  document.getElementById('product-stock').value = product.stock || 0;
  document.getElementById('product-description').value = product.description || '';
  document.getElementById('product-featured').checked = product.featured || false;

  // Show existing image
  const urlInput = document.getElementById('product-image-url');
  if (urlInput) urlInput.value = '';

  setTimeout(() => updatePreview(product.image ? resolveImageUrl(product.image) : null), 10);

  openModal('product-modal');
}

// Save Product
async function saveProduct() {
  const name = document.getElementById('product-name').value.trim();
  const category = document.getElementById('product-category').value;
  const price = document.getElementById('product-price').value;
  const stock = document.getElementById('product-stock').value;

  if (!name) {
    showToast('Nama produk wajib diisi!', 'error');
    document.getElementById('product-name').focus();
    return;
  }
  if (!category) {
    showToast('Pilih kategori produk!', 'error');
    document.getElementById('product-category').focus();
    return;
  }
  if (!price || parseInt(price) < 0) {
    showToast('Harga produk wajib diisi!', 'error');
    document.getElementById('product-price').focus();
    return;
  }
  if (stock === '' || parseInt(stock) < 0) {
    showToast('Stok produk wajib diisi!', 'error');
    document.getElementById('product-stock').focus();
    return;
  }

  // Determine image
  let finalImage = null;

  // 1. If new file was uploaded (base64), upload to server first
  if (uploadedImageBase64) {
    try {
      const blob = dataURLtoBlob(uploadedImageBase64);
      const formData = new FormData();
      formData.append('file', blob, 'product_image.jpg');
      formData.append('type', 'product');

      const uploadResponse = await fetch('../api/upload.php', {
        method: 'POST',
        body: formData
      });
      const uploadResult = await uploadResponse.json();

      if (uploadResult.success && uploadResult.filepath) {
        finalImage = uploadResult.filepath; // e.g. 'uploads/product_xxx.jpg'
      } else {
        console.warn('Upload server gagal:', uploadResult.message);
        showToast('Gagal upload gambar: ' + (uploadResult.message || 'Unknown error'), 'error');
        return;
      }
    } catch (uploadErr) {
      console.warn('Upload gagal:', uploadErr);
      showToast('Gagal upload gambar ke server', 'error');
      return;
    }
  } else {
    // 2. Check URL input
    const urlInput = document.getElementById('product-image-url')?.value?.trim();
    if (urlInput) {
      finalImage = urlInput;
    } else if (editingProductId) {
      // 3. Keep existing image when editing
      const existingProduct = cachedProducts.find(p => p.id === editingProductId);
      if (existingProduct) finalImage = existingProduct.image;
    }
  }

  // If no image at all, use a default
  if (!finalImage) {
    finalImage = 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400';
  }

  const productData = {
    name: name,
    category: category,
    price: parseInt(price),
    stock: parseInt(stock),
    description: document.getElementById('product-description').value.trim(),
    featured: document.getElementById('product-featured').checked,
    image: finalImage
  };

  // Disable save button
  const saveBtn = document.querySelector('#product-form button[type="submit"]');
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
  }

  try {
    const apiData = { ...productData };
    if (editingProductId) apiData.id = editingProductId;

    const response = await fetch('../api/products.php', {
      method: editingProductId ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(apiData)
    });

    const result = await response.json();

    if (result.success) {
      showToast(editingProductId ? 'Produk berhasil diupdate!' : 'Produk berhasil ditambahkan!', 'success');
      closeModal('product-modal');
      // Reload from database to keep in sync
      await loadProductsTable();
    } else {
      showToast('Gagal: ' + (result.message || 'Terjadi kesalahan'), 'error');
    }
  } catch (err) {
    console.error('Error saving product:', err);
    showToast('Gagal menyimpan produk. Pastikan server berjalan.', 'error');
  } finally {
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="fas fa-save"></i> Simpan Produk';
    }
  }
}

// Delete Product
async function deleteProduct(productId) {
  if (!confirm('Apakah Anda yakin ingin menghapus produk ini?')) return;

  try {
    const response = await fetch(`../api/products.php?id=${productId}`, {
      method: 'DELETE'
    });
    const result = await response.json();

    if (result.success) {
      showToast('Produk berhasil dihapus', 'info');
      // Reload from database to keep in sync
      await loadProductsTable();
    } else {
      showToast('Gagal menghapus: ' + (result.message || 'Terjadi kesalahan'), 'error');
    }
  } catch (err) {
    console.error('Error deleting product:', err);
    showToast('Gagal menghapus produk. Pastikan server berjalan.', 'error');
  }
}

// Image Upload
function initImageUpload() {
  const input = document.getElementById('image-input');
  const preview = document.getElementById('image-preview');
  const dropZone = preview;

  if (!input) return;

  // Drag-drop on preview area
  if (dropZone) {
    dropZone.addEventListener('dragover', (e) => {
      e.preventDefault();
      dropZone.style.borderColor = 'var(--primary)';
    });

    dropZone.addEventListener('dragleave', () => {
      dropZone.style.borderColor = '';
    });

    dropZone.addEventListener('drop', (e) => {
      e.preventDefault();
      dropZone.style.borderColor = '';
      handleFiles(e.dataTransfer.files);
    });
  }

  input.addEventListener('change', () => {
    handleFiles(input.files);
  });

  function handleFiles(files) {
    if (files.length === 0) return;
    const file = files[0];
    if (!file.type.startsWith('image/')) {
      showToast('File harus berupa gambar', 'error');
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      showToast('Ukuran gambar terlalu besar! Maksimal 5MB.', 'error');
      return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
      const img = new Image();
      img.onload = function () {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

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

        const dataUrl = canvas.toDataURL('image/jpeg', 0.7);

        uploadedImageBase64 = dataUrl;
        const urlInput = document.getElementById('product-image-url');
        if (urlInput) urlInput.value = '';
        updatePreview(dataUrl);
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }

  // Support URL change preview
  document.getElementById('product-image-url')?.addEventListener('input', function () {
    if (this.value) {
      uploadedImageBase64 = null;
      updatePreview(this.value);
    } else {
      updatePreview(null);
    }
  });
}

function updatePreview(src) {
  const preview = document.getElementById('image-preview');
  if (!preview) return;

  if (src) {
    preview.innerHTML = `
      <div style="position: relative; width: 100%; height: 100%;">
        <img src="${src}" alt="Preview" style="width: 100%; height: 100%; object-fit: contain;" onerror="this.src='https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400'">
        <button type="button" onclick="resetImagePreview()" 
                style="position: absolute; top: 10px; right: 10px; background: rgba(239, 68, 68, 0.9); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">
          <i class="fas fa-times"></i>
        </button>
      </div>
    `;
  } else {
    preview.innerHTML = `
      <div class="no-image-placeholder">
        <i class="fas fa-image"></i>
        <span>Preview Gambar</span>
      </div>
    `;
  }
}

function resetImagePreview() {
  uploadedImageBase64 = null;
  const urlInput = document.getElementById('product-image-url');
  if (urlInput) urlInput.value = '';
  const fileInput = document.getElementById('image-input');
  if (fileInput) fileInput.value = '';
  updatePreview(null);
}

// Search and Filter - use debounced re-render (no re-fetch needed)
let searchTimeout = null;
document.getElementById('search-products')?.addEventListener('input', function() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => renderProductsTable(cachedProducts), 300);
});
document.getElementById('filter-category')?.addEventListener('change', function() {
  renderProductsTable(cachedProducts);
});

// Export functions to window for onclick handlers
window.openAddProduct = openAddProduct;
window.editProduct = editProduct;
window.deleteProduct = deleteProduct;
window.saveProduct = saveProduct;
window.resetImagePreview = resetImagePreview;

// Helper: convert dataURL (base64) to Blob for multipart upload
function dataURLtoBlob(dataURL) {
  const parts = dataURL.split(';base64,');
  const mimeType = parts[0].split(':')[1];
  const raw = atob(parts[1]);
  const arrayBuffer = new ArrayBuffer(raw.length);
  const uint8 = new Uint8Array(arrayBuffer);
  for (let i = 0; i < raw.length; i++) {
    uint8[i] = raw.charCodeAt(i);
  }
  return new Blob([arrayBuffer], { type: mimeType });
}

// Helper: resolve image URL correctly for admin pages
function resolveImageUrl(src) {
  if (!src) return 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400';
  // Full URL or data URL: use as-is
  if (src.startsWith('http') || src.startsWith('data:') || src.startsWith('/')) return src;
  // Local path (uploads/xxx.jpg): add '../' because admin page is in /admin/ subfolder
  if (src.startsWith('uploads/')) return '../' + src;
  // Already has '../' prefix: use as-is
  if (src.startsWith('../uploads/')) return src;
  return src;
}
