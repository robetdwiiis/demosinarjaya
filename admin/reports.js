// Reports System
document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('reports-container')) {
    initReportsSystem();
  }
});

function initReportsSystem() {
  loadSalesReport();
  loadStockReport();
  initReportFilters();
}

// Sales Report
function loadSalesReport() {
  const container = document.getElementById('sales-report');
  if (!container) return;

  const salesData = Storage.get('salesData') || SinarJayaData.salesData;
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const year = document.getElementById('report-year')?.value || '2025';
  const data = salesData[year] || [];

  const total = data.reduce((a, b) => a + b, 0);
  const avg = Math.round(total / data.length);
  const max = Math.max(...data);
  const maxMonth = months[data.indexOf(max)];

  container.innerHTML = `
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
      <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-lg); text-align: center;">
        <span style="color: var(--gray-500); font-size: 0.875rem;">Total Penjualan</span>
        <strong style="display: block; font-size: 1.5rem; color: var(--primary);">${formatNumber(total)}</strong>
      </div>
      <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-lg); text-align: center;">
        <span style="color: var(--gray-500); font-size: 0.875rem;">Rata-rata/Bulan</span>
        <strong style="display: block; font-size: 1.5rem; color: var(--success);">${formatNumber(avg)}</strong>
      </div>
      <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-lg); text-align: center;">
        <span style="color: var(--gray-500); font-size: 0.875rem;">Tertinggi</span>
        <strong style="display: block; font-size: 1.5rem; color: var(--secondary);">${formatNumber(max)}</strong>
      </div>
      <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-lg); text-align: center;">
        <span style="color: var(--gray-500); font-size: 0.875rem;">Bulan Terbaik</span>
        <strong style="display: block; font-size: 1.5rem; color: var(--accent);">${maxMonth}</strong>
      </div>
    </div>
    
    <table class="data-table">
      <thead>
        <tr>
          <th>Bulan</th>
          <th>Penjualan</th>
          <th>Persentase</th>
          <th>Trend</th>
        </tr>
      </thead>
      <tbody>
        ${data.map((sales, i) => {
    const prevSales = i > 0 ? data[i - 1] : sales;
    const change = prevSales > 0 ? ((sales - prevSales) / prevSales * 100).toFixed(1) : '0.0';
    const percentage = total > 0 ? ((sales / total) * 100).toFixed(1) : '0.0';
    return `
            <tr>
              <td>${months[i]}</td>
              <td><strong>${formatNumber(sales)}</strong></td>
              <td>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                  <div style="flex: 1; height: 8px; background: var(--gray-200); border-radius: var(--radius-full);">
                    <div style="height: 100%; width: ${percentage}%; background: var(--primary-gradient); border-radius: var(--radius-full);"></div>
                  </div>
                  <span style="font-size: 0.875rem;">${percentage}%</span>
                </div>
              </td>
              <td>
                <span style="color: ${parseFloat(change) >= 0 ? 'var(--success)' : 'var(--error)'};">
                  <i class="fas fa-arrow-${parseFloat(change) >= 0 ? 'up' : 'down'}"></i>
                  ${change}%
                </span>
              </td>
            </tr>
          `;
  }).join('')}
      </tbody>
    </table>
  `;
}

// Stock Report
function loadStockReport() {
  const container = document.getElementById('stock-report');
  if (!container) return;

  const products = Storage.get('products') || SinarJayaData.products;

  // Group by category
  const categoryStats = {};
  SinarJayaData.categories.forEach(cat => {
    const catProducts = products.filter(p => p.category === cat.id);
    const totalStock = catProducts.reduce((sum, p) => sum + p.stock, 0);
    const totalValue = catProducts.reduce((sum, p) => sum + (p.stock * p.price), 0);
    categoryStats[cat.id] = {
      name: cat.name,
      count: catProducts.length,
      stock: totalStock,
      value: totalValue,
      lowStock: catProducts.filter(p => p.stock < 50).length
    };
  });

  const totalStock = Object.values(categoryStats).reduce((sum, c) => sum + c.stock, 0);
  const totalValue = Object.values(categoryStats).reduce((sum, c) => sum + c.value, 0);

  container.innerHTML = `
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
      <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-lg); text-align: center;">
        <span style="color: var(--gray-500); font-size: 0.875rem;">Total Stok</span>
        <strong style="display: block; font-size: 1.5rem; color: var(--primary);">${formatNumber(totalStock)} pcs</strong>
      </div>
      <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-lg); text-align: center;">
        <span style="color: var(--gray-500); font-size: 0.875rem;">Nilai Inventori</span>
        <strong style="display: block; font-size: 1.5rem; color: var(--success);">${formatCurrency(totalValue)}</strong>
      </div>
      <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-lg); text-align: center;">
        <span style="color: var(--gray-500); font-size: 0.875rem;">Stok Menipis</span>
        <strong style="display: block; font-size: 1.5rem; color: var(--warning);">
          ${Object.values(categoryStats).reduce((sum, c) => sum + c.lowStock, 0)} produk
        </strong>
      </div>
    </div>
    
    <table class="data-table">
      <thead>
        <tr>
          <th>Kategori</th>
          <th>Jumlah Produk</th>
          <th>Total Stok</th>
          <th>Nilai</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        ${Object.values(categoryStats).map(cat => `
          <tr>
            <td><strong>${cat.name}</strong></td>
            <td>${cat.count} produk</td>
            <td>${formatNumber(cat.stock)} pcs</td>
            <td>${formatCurrency(cat.value)}</td>
            <td>
              <span class="status-badge ${cat.lowStock === 0 ? 'active' : 'low'}">
                ${cat.lowStock === 0 ? 'Aman' : `${cat.lowStock} perlu restock`}
              </span>
            </td>
          </tr>
        `).join('')}
      </tbody>
    </table>
  `;
}

// Report Filters
function initReportFilters() {
  document.getElementById('report-year')?.addEventListener('change', loadSalesReport);
  document.getElementById('report-type')?.addEventListener('change', switchReportType);
}

function switchReportType(e) {
  const type = e.target.value;
  document.querySelectorAll('.report-section').forEach(section => {
    section.style.display = 'none';
  });
  document.getElementById(`${type}-section`)?.style.display = 'block';
}

// ============================================
// EXPORT REPORTS - Excel & PDF
// ============================================

// Get current active report type
function getCurrentReportType() {
  const activeTab = document.querySelector('.algorithm-tab.active');
  if (!activeTab) return 'sales';
  const text = activeTab.textContent.toLowerCase();
  if (text.includes('stok')) return 'stock';
  if (text.includes('prediksi')) return 'prediction';
  return 'sales';
}

// Get report data based on type
function getReportData(type) {
  const year = document.getElementById('report-year')?.value || '2025';
  const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

  if (type === 'sales') {
    const salesData = Storage.get('salesData') || SinarJayaData.salesData;
    const data = salesData[year] || [];
    return {
      title: `Laporan Penjualan Tahun ${year}`,
      headers: ['Bulan', 'Penjualan', 'Persentase'],
      rows: data.map((sales, i) => {
        const total = data.reduce((a, b) => a + b, 0);
        const percentage = ((sales / total) * 100).toFixed(1);
        return [months[i], sales.toString(), `${percentage}%`];
      }),
      summary: {
        'Total Penjualan': formatNumber(data.reduce((a, b) => a + b, 0)),
        'Rata-rata/Bulan': formatNumber(Math.round(data.reduce((a, b) => a + b, 0) / data.length)),
        'Penjualan Tertinggi': formatNumber(Math.max(...data)),
        'Penjualan Terendah': formatNumber(Math.min(...data))
      }
    };
  }

  if (type === 'stock') {
    const products = Storage.get('products') || SinarJayaData.products;
    const categoryStats = {};
    SinarJayaData.categories.forEach(cat => {
      const catProducts = products.filter(p => p.category === cat.id);
      const totalStock = catProducts.reduce((sum, p) => sum + p.stock, 0);
      const totalValue = catProducts.reduce((sum, p) => sum + (p.stock * p.price), 0);
      categoryStats[cat.id] = {
        name: cat.name,
        count: catProducts.length,
        stock: totalStock,
        value: totalValue,
        lowStock: catProducts.filter(p => p.stock < 50).length
      };
    });

    return {
      title: 'Laporan Stok Barang',
      headers: ['Kategori', 'Jumlah Produk', 'Total Stok', 'Nilai Inventori', 'Status'],
      rows: Object.values(categoryStats).map(cat => [
        cat.name,
        cat.count.toString(),
        cat.stock.toString(),
        formatCurrency(cat.value),
        cat.lowStock === 0 ? 'Aman' : `${cat.lowStock} perlu restock`
      ]),
      summary: {
        'Total Stok': formatNumber(Object.values(categoryStats).reduce((sum, c) => sum + c.stock, 0)) + ' pcs',
        'Nilai Inventori': formatCurrency(Object.values(categoryStats).reduce((sum, c) => sum + c.value, 0)),
        'Produk Perlu Restock': Object.values(categoryStats).reduce((sum, c) => sum + c.lowStock, 0).toString()
      }
    };
  }

  if (type === 'prediction') {
    return {
      title: 'Laporan Prediksi Stok Tahunan',
      headers: ['Kategori', 'Q1', 'Q2', 'Q3', 'Q4', 'Total'],
      rows: [
        ['Kemeja', '550', '600', '580', '670', '2400'],
        ['Polo Shirt', '750', '800', '780', '870', '3200'],
        ['Jaket', '250', '280', '320', '350', '1200'],
        ['Seragam', '400', '450', '420', '530', '1800']
      ],
      summary: {
        'Total Prediksi': '8,600 pcs',
        'Kategori Tertinggi': 'Polo Shirt (3,200 pcs)',
        'Pertumbuhan Rata-rata': '+13%'
      }
    };
  }

  return null;
}

// Export to Excel (CSV format)
function exportReportExcel(type) {
  const reportType = type === 'current' ? getCurrentReportType() : type;
  const data = getReportData(reportType);

  if (!data) {
    showToast('Gagal mengambil data laporan', 'error');
    return;
  }

  showToast('Menyiapkan file Excel...', 'info');

  // Build CSV content
  let csvContent = '\uFEFF'; // BOM for UTF-8

  // Title
  csvContent += `"${data.title}"\n`;
  csvContent += `"Tanggal Export: ${new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}"\n`;
  csvContent += `"SINAR JAYA KONVEKSI"\n\n`;

  // Headers
  csvContent += data.headers.map(h => `"${h}"`).join(',') + '\n';

  // Data rows
  data.rows.forEach(row => {
    csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
  });

  // Summary
  csvContent += '\n"RINGKASAN"\n';
  Object.entries(data.summary).forEach(([key, value]) => {
    csvContent += `"${key}","${value}"\n`;
  });

  // Create and download file
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const fileName = `Laporan_${reportType}_${new Date().toISOString().slice(0, 10)}.csv`;

  link.href = URL.createObjectURL(blob);
  link.download = fileName;
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  showToast(`File ${fileName} berhasil diunduh!`, 'success');
}

// Export to PDF (using print)
function exportReportPDF(type) {
  console.log('exportReportPDF called with type:', type);

  try {
    const reportType = type === 'current' ? getCurrentReportType() : type;
    const data = getReportData(reportType);

    if (!data) {
      showToast('Gagal mengambil data laporan', 'error');
      return;
    }

    showToast('Menyiapkan file PDF...', 'info');

    // Create print window
    const printWindow = window.open('', '_blank', 'width=900,height=700');

    // Check if popup was blocked
    if (!printWindow || printWindow.closed || typeof printWindow.closed === 'undefined') {
      showToast('Pop-up diblokir! Izinkan pop-up untuk mencetak PDF.', 'error');
      alert('Pop-up diblokir oleh browser!\n\nUntuk mengekspor PDF:\n1. Klik ikon blokir pop-up di address bar\n2. Pilih "Always allow pop-ups"\n3. Coba lagi');
      return;
    }

    const printContent = `
    <!DOCTYPE html>
    <html>
    <head>
        <title>${data.title} - SINAR JAYA KONVEKSI</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Arial, sans-serif; 
                padding: 40px;
                color: #333;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #1e3a5f;
            }
            .logo { 
                font-size: 24px; 
                font-weight: bold; 
                color: #1e3a5f;
                margin-bottom: 5px;
            }
            .logo span { color: #f59e0b; }
            .subtitle { color: #666; font-size: 14px; }
            .report-title {
                font-size: 20px;
                font-weight: bold;
                margin: 20px 0 10px;
                color: #1e3a5f;
            }
            .date {
                font-size: 12px;
                color: #888;
                margin-bottom: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 12px 8px;
                text-align: left;
            }
            th {
                background: #1e3a5f;
                color: white;
                font-weight: 600;
            }
            tr:nth-child(even) { background: #f8f9fa; }
            .summary {
                margin-top: 30px;
                padding: 20px;
                background: #f0f9ff;
                border-radius: 8px;
            }
            .summary h3 {
                margin-bottom: 15px;
                color: #1e3a5f;
            }
            .summary-item {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #e0e0e0;
            }
            .summary-item:last-child { border-bottom: none; }
            .footer {
                margin-top: 40px;
                text-align: center;
                font-size: 12px;
                color: #888;
                padding-top: 20px;
                border-top: 1px solid #ddd;
            }
            @media print {
                body { padding: 20px; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="logo">SINAR JAYA <span>KONVEKSI</span></div>
            <div class="subtitle">Desa Padurenan, Kecamatan Gebog, Kabupaten Kudus, Jawa Tengah</div>
            <div class="subtitle">WhatsApp: +62 856 4735 2998 | Email: info@sinarjaya-konveksi.com</div>
        </div>
        
        <div class="report-title">${data.title}</div>
        <div class="date">Tanggal Cetak: ${new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</div>
        
        <table>
            <thead>
                <tr>
                    ${data.headers.map(h => `<th>${h}</th>`).join('')}
                </tr>
            </thead>
            <tbody>
                ${data.rows.map(row => `
                    <tr>
                        ${row.map(cell => `<td>${cell}</td>`).join('')}
                    </tr>
                `).join('')}
            </tbody>
        </table>
        
        <div class="summary">
            <h3>Ringkasan</h3>
            ${Object.entries(data.summary).map(([key, value]) => `
                <div class="summary-item">
                    <span>${key}</span>
                    <strong>${value}</strong>
                </div>
            `).join('')}
        </div>
        
        <div class="footer">
            <p>Dokumen ini dicetak secara otomatis oleh Sistem SINAR JAYA KONVEKSI</p>
            <p>© ${new Date().getFullYear()} SINAR JAYA KONVEKSI - Semua Hak Dilindungi</p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; cursor: pointer; background: #1e3a5f; color: white; border: none; border-radius: 5px;">
                🖨️ Cetak / Simpan PDF
            </button>
            <p style="margin-top: 10px; color: #666; font-size: 12px;">
                Klik tombol di atas, lalu pilih "Save as PDF" untuk menyimpan sebagai file PDF
            </p>
        </div>
    </body>
    </html>
    `;

    printWindow.document.write(printContent);
    printWindow.document.close();

    setTimeout(() => {
      showToast('Silakan pilih "Save as PDF" untuk menyimpan', 'success');
    }, 500);
  } catch (error) {
    console.error('Error exporting PDF:', error);
    showToast('Terjadi kesalahan saat mengekspor PDF: ' + error.message, 'error');
  }
}

// Export functions to global scope
window.loadSalesReport = loadSalesReport;
window.loadStockReport = loadStockReport;
window.exportReportPDF = exportReportPDF;
window.exportReportExcel = exportReportExcel;
window.getCurrentReportType = getCurrentReportType;
