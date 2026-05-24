// Products Page JavaScript - Premium Design
let allProducts = [];

document.addEventListener('DOMContentLoaded', function () {
  if (document.querySelector('.products-grid')) {
    initProductsPage();
  }
});

async function initProductsPage() {
  await loadProductsFromAPI();

  // Check URL parameter for category filter
  const urlParams = new URLSearchParams(window.location.search);
  const catParam = urlParams.get('cat');
  if (catParam) {
    const btn = document.querySelector(`.filter-btn[data-category="${catParam}"]`);
    if (btn) {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentCategory = catParam;
    }
  }

  renderProducts();
  initFilters();
}

let currentCategory = 'all';
let searchQuery = '';

async function loadProductsFromAPI() {
  try {
    const response = await fetch('api/products.php?status=active&limit=100');
    const result = await response.json();

    if (result.success && result.data) {
      allProducts = result.data;
      Storage.set('products', allProducts);
      return;
    }
  } catch (err) {
    console.warn('API tidak tersedia, menggunakan localStorage:', err);
  }

  allProducts = Storage.get('products') || SinarJayaData.products || [];
}

function renderProducts() {
  const grid = document.querySelector('.products-grid');
  if (!grid) return;

  let filteredProducts = allProducts;

  if (currentCategory !== 'all') {
    filteredProducts = filteredProducts.filter(p => p.category === currentCategory);
  }

  if (searchQuery) {
    const query = searchQuery.toLowerCase();
    filteredProducts = filteredProducts.filter(p =>
      (p.name || '').toLowerCase().includes(query) ||
      (p.description || '').toLowerCase().includes(query)
    );
  }

  // Update product count label
  const countLabel = document.getElementById('product-count-label');

  if (filteredProducts.length === 0) {
    if (countLabel) countLabel.textContent = 'Tidak ada produk ditemukan';
    grid.innerHTML = `
      <div class="no-products" style="grid-column: 1/-1; text-align: center; padding: 4rem 2rem;">
        <div style="width:80px;height:80px;margin:0 auto 1.25rem;background:var(--gray-100);border-radius:50%;display:flex;align-items:center;justify-content:center;">
          <i class="fas fa-box-open" style="font-size: 2rem; color: var(--gray-400);"></i>
        </div>
        <h4 style="color: var(--gray-700); margin-bottom: 0.5rem; font-size: 1.1rem;">Tidak ada produk ditemukan</h4>
        <p style="color: var(--gray-500); font-size: 0.875rem;">Coba ubah filter atau kata kunci pencarian</p>
      </div>
    `;
    return;
  }

  // Show count
  if (countLabel) {
    if (currentCategory !== 'all' || searchQuery) {
      countLabel.textContent = `${filteredProducts.length} dari ${allProducts.length} produk`;
    } else {
      countLabel.textContent = `Menampilkan ${filteredProducts.length} produk`;
    }
  }

  grid.innerHTML = filteredProducts.map((product, index) => {
    const stockClass = product.stock > 50 ? 'in-stock' : product.stock > 20 ? 'low-stock' : 'out-stock';
    const stockLabel = product.stock > 50 ? 'Tersedia' : product.stock > 20 ? 'Stok Terbatas' : 'Hampir Habis';
    const price = formatCurrency(product.price);
    const desc = (product.description || '').substring(0, 80);

    return `
      <div class="product-card fade-in visible" data-category="${product.category}" style="animation-delay: ${index * 0.05}s">
        <div class="product-image">
          <img src="${resolveImageUrl(product.image)}" 
               alt="${product.name}" loading="lazy"
               onerror="this.src='https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400'">
          ${product.featured ? '<span class="product-badge"><i class="fas fa-crown" style="margin-right:3px;font-size:0.6rem"></i> Unggulan</span>' : ''}
        </div>
        <div class="product-content">
          <span class="product-category">${getCategoryNameById(product.category)}</span>
          <h4 class="product-title">${product.name}</h4>
          <p class="product-desc">${desc ? desc + (product.description && product.description.length > 80 ? '...' : '') : 'Produk berkualitas dari Sinar Jaya Konveksi'}</p>
          <div class="product-meta">
            <span class="product-price">${price}</span>
            <span class="product-stock ${stockClass}">
              <i class="fas fa-circle" style="font-size:0.35rem"></i> ${stockLabel}
            </span>
          </div>
          <button class="product-action" onclick="viewProductDetail(${product.id})">
            Lihat Detail <i class="fas fa-arrow-right"></i>
          </button>
        </div>
      </div>
    `;
  }).join('');
}

function initFilters() {
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentCategory = btn.dataset.category;
      renderProducts();
    });
  });

  const searchInput = document.getElementById('product-search');
  if (searchInput) {
    let timeout;
    searchInput.addEventListener('input', (e) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => {
        searchQuery = e.target.value;
        renderProducts();
      }, 250);
    });
  }
}

function getCategoryNameById(categoryId) {
  const cat = SinarJayaData.categories.find(c => c.id === categoryId);
  return cat ? cat.name : (categoryId || 'Produk');
}

// View product detail - Premium Modal
function viewProductDetail(productId) {
  const product = allProducts.find(p => p.id == productId);

  if (!product) {
    showToast('Produk tidak ditemukan', 'error');
    return;
  }

  const modal = document.getElementById('product-modal');
  if (!modal) return;

  const modalBody = modal.querySelector('.modal-body');
  const waNumber = '6285647352998';
  const waMsg = encodeURIComponent(`Halo, saya tertarik dengan produk *${product.name}*. Bisa info lebih lanjut?`);

  const stockClass = product.stock > 50 ? 'stock-ok' : product.stock > 20 ? 'stock-low' : 'stock-out';
  const materialInfo = product.material ? `
    <div class="detail-material">
      <i class="fas fa-layer-group"></i> Bahan: ${product.material}
    </div>
  ` : '';

  modalBody.innerHTML = `
    <div class="product-detail-grid">
      <div class="product-detail-image">
        <img src="${resolveImageUrl(product.image)}" 
             alt="${product.name}" 
             onerror="this.src='https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400'">
      </div>
      <div class="product-detail-info">
        <span class="detail-category">
          <i class="fas fa-tag"></i> ${getCategoryNameById(product.category)}
        </span>
        <h3 class="detail-name">${product.name}</h3>
        ${materialInfo}
        <p class="detail-desc">
          ${product.description || 'Produk berkualitas tinggi dari Sinar Jaya Konveksi. Dibuat dengan bahan pilihan dan proses produksi terstandar.'}
        </p>

        <div class="detail-specs">
          <div class="detail-spec-item">
            <span class="spec-label">Harga</span>
            <span class="spec-value price-value">${formatCurrency(product.price)}</span>
          </div>
          <div class="detail-spec-item">
            <span class="spec-label">Stok</span>
            <span class="spec-value ${stockClass}">${product.stock} pcs</span>
          </div>
          ${product.min_order ? `
          <div class="detail-spec-item">
            <span class="spec-label">Min. Order</span>
            <span class="spec-value">${product.min_order} pcs</span>
          </div>
          ` : ''}
          ${product.sizes ? `
          <div class="detail-spec-item">
            <span class="spec-label">Ukuran</span>
            <span class="spec-value" style="font-size:0.85rem">${product.sizes}</span>
          </div>
          ` : ''}
        </div>

        <div class="detail-actions">
          <a href="https://wa.me/${waNumber}?text=${waMsg}" target="_blank" class="btn-wa">
            <i class="fab fa-whatsapp"></i> Pesan via WhatsApp
          </a>
          <a href="contact.html" class="btn-contact">
            <i class="fas fa-envelope"></i> Hubungi Kami
          </a>
        </div>
      </div>
    </div>
  `;

  openModal('product-modal');
}

window.viewProductDetail = viewProductDetail;

// Helper: resolve image URL correctly from public pages (root level)
function resolveImageUrl(src) {
  if (!src) return 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=400';
  if (src.startsWith('http') || src.startsWith('data:') || src.startsWith('/')) return src;
  if (src.startsWith('../uploads/')) return src.replace('../', '');
  return src;
}
