// Admin Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function () {
    initAdminDashboard();
});

function initAdminDashboard() {
    initSidebar();
    loadDashboardStats();
    initSalesChart();
    loadLowStockAlerts();
    loadRecentProducts();
}

// Sidebar Toggle
function initSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const mobileToggle = document.querySelector('.mobile-toggle');
    const sidebarToggle = document.querySelector('.sidebar-toggle');

    mobileToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
    });

    sidebarToggle?.addEventListener('click', () => {
        sidebar?.classList.remove('open');
    });

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 968 && sidebar?.classList.contains('open')) {
            if (!sidebar.contains(e.target) && !mobileToggle?.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
}

// Load Dashboard Statistics
function loadDashboardStats() {
    const products = Storage.get('products') || SinarJayaData.products;
    const salesData = Storage.get('salesData') || SinarJayaData.salesData;

    const totalProducts = products.length;
    const totalStock = products.reduce((sum, p) => sum + p.stock, 0);
    const lowStockCount = products.filter(p => p.stock < 50).length;

    // Calculate total sales
    const currentYearSales = salesData['2025'] || [];
    const totalSales = currentYearSales.reduce((sum, s) => sum + s, 0);

    // Update stat cards
    updateStatCard('total-products', totalProducts, '+12%', 'up');
    updateStatCard('total-stock', formatNumber(totalStock), '+8%', 'up');
    updateStatCard('total-sales', formatNumber(totalSales), '+15%', 'up');
    updateStatCard('low-stock', lowStockCount, lowStockCount > 0 ? 'Perlu diperhatikan' : 'Aman', lowStockCount > 0 ? 'down' : 'up');
}

function updateStatCard(id, value, trend, direction) {
    const card = document.getElementById(id);
    if (!card) return;

    const valueEl = card.querySelector('h3');
    const trendEl = card.querySelector('.stat-card-trend');

    if (valueEl) valueEl.textContent = value;
    if (trendEl) {
        trendEl.innerHTML = direction === 'up'
            ? `<i class="fas fa-arrow-up"></i> ${trend}`
            : `<i class="fas fa-arrow-down"></i> ${trend}`;
        trendEl.className = `stat-card-trend ${direction}`;
    }
}

// Sales Chart
function initSalesChart() {
    const ctx = document.getElementById('salesChart');
    if (!ctx) return;

    const salesData = Storage.get('salesData') || SinarJayaData.salesData;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Penjualan 2024',
                    data: salesData['2024'] || [],
                    borderColor: '#94a3b8',
                    backgroundColor: 'rgba(148, 163, 184, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Penjualan 2025',
                    data: salesData['2025'] || [],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
}

// Low Stock Alerts
function loadLowStockAlerts() {
    const container = document.getElementById('low-stock-list');
    if (!container) return;

    const products = Storage.get('products') || SinarJayaData.products;
    const lowStock = products.filter(p => p.stock < 50).sort((a, b) => a.stock - b.stock);

    if (lowStock.length === 0) {
        container.innerHTML = `
      <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
        <i class="fas fa-check-circle" style="font-size: 2rem; color: var(--success); margin-bottom: 0.5rem;"></i>
        <p>Semua stok dalam kondisi aman</p>
      </div>
    `;
        return;
    }

    container.innerHTML = lowStock.slice(0, 5).map(product => `
    <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--gray-100);">
      <img src="${product.image}" alt="${product.name}" style="width: 40px; height: 40px; border-radius: var(--radius); object-fit: cover;">
      <div style="flex: 1;">
        <strong style="font-size: 0.875rem;">${product.name}</strong>
        <span style="display: block; font-size: 0.75rem; color: ${product.stock < 20 ? 'var(--error)' : 'var(--warning)'};">
          Stok: ${product.stock} pcs
        </span>
      </div>
      <button class="btn btn-sm btn-secondary" onclick="location.href='products.html?edit=${product.id}'">
        <i class="fas fa-plus"></i>
      </button>
    </div>
  `).join('');
}

// Recent Products
function loadRecentProducts() {
    const container = document.getElementById('recent-products');
    if (!container) return;

    const products = Storage.get('products') || SinarJayaData.products;
    const recent = products.slice(-5).reverse();

    container.innerHTML = `
    <table class="data-table">
      <thead>
        <tr>
          <th>Produk</th>
          <th>Kategori</th>
          <th>Harga</th>
          <th>Stok</th>
        </tr>
      </thead>
      <tbody>
        ${recent.map(p => `
          <tr>
            <td>
              <div class="product-cell">
                <img src="${p.image}" alt="${p.name}">
                <span>${p.name}</span>
              </div>
            </td>
            <td>${getCategoryName(p.category)}</td>
            <td>${formatCurrency(p.price)}</td>
            <td>
              <span class="status-badge ${p.stock > 50 ? 'active' : p.stock > 20 ? 'low' : 'out'}">
                ${p.stock} pcs
              </span>
            </td>
          </tr>
        `).join('')}
      </tbody>
    </table>
  `;
}

function getCategoryName(categoryId) {
    const cat = SinarJayaData.categories.find(c => c.id === categoryId);
    return cat ? cat.name : categoryId;
}

// Export functions
window.loadDashboardStats = loadDashboardStats;
window.initSalesChart = initSalesChart;
