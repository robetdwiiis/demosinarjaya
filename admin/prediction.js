// Stock Prediction System - Database Powered
let stockHistoryData = []; // Store data globally
let currentAlgorithm = 'moving-average';
let predictionChart = null;

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('prediction-chart')) {
        initPredictionSystem();
    }
});

async function initPredictionSystem() {
    // Show loading state
    const chartContainer = document.querySelector('.chart-container');
    if (chartContainer) {
        chartContainer.style.opacity = '0.5';
    }

    await loadStockDataFromAPI();
    initAlgorithmTabs();

    // Initial run
    const category = document.getElementById('prediction-category')?.value || 'kemeja';
    runPrediction(category);

    if (chartContainer) {
        chartContainer.style.opacity = '1';
    }
}

// Initialize Algorithm Tabs and Category Selection
function initAlgorithmTabs() {
    const tabs = document.querySelectorAll('.tab-btn');
    const categorySelect = document.getElementById('prediction-category');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');

            // Update current algorithm
            currentAlgorithm = this.getAttribute('data-algorithm');

            // Re-run prediction
            const category = categorySelect?.value || 'kemeja';
            runPrediction(category);
        });
    });

    // Handle category change
    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            runPrediction(this.value);
        });
    }
}

// Get historical data from API (Database)
async function loadStockDataFromAPI() {
    try {
        const response = await fetch('../api/stock-history.php');
        const result = await response.json();

        if (result.success && result.stockHistory) {
            stockHistoryData = result.stockHistory;
            console.log('Data loaded from database:', stockHistoryData.length, 'records');
            // Data will be shown after runPrediction parses it
        } else {
            console.error('Failed to load data:', result.message);
            showToast('Gagal memuat data dari database', 'error');
            // Fallback to local/dummy data if DB fails
            stockHistoryData = SinarJayaData.stockHistory;
        }
    } catch (error) {
        console.error('API Error:', error);
        showToast('Terjadi kesalahan koneksi server', 'error');
        stockHistoryData = SinarJayaData.stockHistory; // Fallback
    }
}

// Get current data (helper)
function getHistoricalData() {
    return stockHistoryData.length > 0 ? stockHistoryData : SinarJayaData.stockHistory;
}

// Helper: Group data by Year (Sum)
function processDataYearly(stockHistory, category) {
    if (!stockHistory || stockHistory.length === 0) return { years: [], values: [] };

    const yearlyMap = {};

    stockHistory.forEach(row => {
        let year = null;
        // Try to parse year from 'month' string (e.g. "Jan 2024" -> "2024")
        if (row.month) {
            const match = row.month.match(/\d{4}/);
            if (match) year = match[0];
        } else if (row.year) {
            year = row.year;
        }

        if (year) {
            if (!yearlyMap[year]) yearlyMap[year] = 0;
            // Summing up monthly data for yearly total necessity
            yearlyMap[year] += (parseInt(row[category]) || 0);
        }
    });

    // Sort by year
    const years = Object.keys(yearlyMap).sort();
    const values = years.map(y => yearlyMap[y]);

    return { years, values };
}

// Moving Average Algorithm (Modified for Yearly)
function movingAverage(data, period = 2) {
    const result = [];
    // If data is too short, use period 1 or simply average
    const effectivePeriod = Math.min(period, Math.max(1, data.length - 1));

    for (let i = 0; i < data.length; i++) {
        if (i < effectivePeriod - 1) {
            result.push(null);
        } else {
            let sum = 0;
            for (let j = 0; j < effectivePeriod; j++) {
                sum += data[i - j];
            }
            result.push(Math.round(sum / effectivePeriod));
        }
    }

    // Predict next 5 YEARS
    const lastValues = data.slice(-effectivePeriod);
    const predictions = [];
    for (let i = 0; i < 5; i++) {
        const avg = Math.round(lastValues.reduce((a, b) => a + b) / effectivePeriod);
        predictions.push(avg);
        lastValues.shift();
        lastValues.push(avg);
    }

    return { smoothed: result, predictions };
}

// Linear Regression Algorithm (Same logic, just context changed)
function linearRegression(data) {
    const n = data.length;
    if (n === 0) return { fitted: [], predictions: [], slope: 0, intercept: 0 };

    let sumX = 0, sumY = 0, sumXY = 0, sumX2 = 0;

    for (let i = 0; i < n; i++) {
        sumX += i;
        sumY += data[i];
        sumXY += i * data[i];
        sumX2 += i * i;
    }

    const denominator = (n * sumX2 - sumX * sumX);
    if (denominator === 0) return { fitted: Array(n).fill(data[0]), predictions: Array(5).fill(data[0]) };

    const slope = (n * sumXY - sumX * sumY) / denominator;
    const intercept = (sumY - slope * sumX) / n;

    // Fitted values
    const fitted = data.map((_, i) => Math.round(intercept + slope * i));

    // Predict next 5 YEARS
    const predictions = [];
    for (let i = 0; i < 5; i++) {
        predictions.push(Math.round(intercept + slope * (n + i)));
    }

    return { fitted, predictions, slope, intercept };
}

// Trend Analysis (Simplified for Yearly - no seasonality usually needed for annual data unless long cycle)
function trendAnalysis(data) {
    // For annual data, linear regression is often sufficient for trend.
    // We will use Linear Regression but labeled as Trend Analysis
    return linearRegression(data);
}

// Run Prediction
function runPrediction(category = 'kemeja') {
    const stockHistory = getHistoricalData();

    // Process data to be YEARLY
    let { years, values } = processDataYearly(stockHistory, category);

    // Fallback Dummy Data if No Data Exists (For Demo Purpose)
    if (years.length === 0) {
        const currentYear = new Date().getFullYear();
        years = [currentYear - 4, currentYear - 3, currentYear - 2, currentYear - 1, currentYear].map(String);
        // Base values per category
        const base = category === 'kemeja' ? 1200 : category === 'polo' ? 2000 : category === 'jaket' ? 800 : 3000;
        // Generate somewhat increasing trend
        values = years.map((_, i) => Math.round(base * (1 + (i * 0.1) + (Math.random() * 0.1))));
    }

    const data = values;
    const labels = years;

    let result;
    let algorithmName;

    switch (currentAlgorithm) {
        case 'moving-average':
            result = movingAverage(data);
            algorithmName = 'Moving Average (Tahunan)';
            break;
        case 'linear-regression':
            result = linearRegression(data);
            algorithmName = 'Linear Regression';
            break;
        case 'trend-analysis':
            result = trendAnalysis(data);
            algorithmName = 'Trend Analysis';
            break;
        default:
            result = movingAverage(data);
            algorithmName = 'Moving Average';
    }

    updatePredictionChart(data, result, labels, algorithmName);
    updatePredictionStats(data, result);
    updateRecommendations(result.predictions, category, labels);
    updateYearlyTable(labels, data, category); // New function for yearly table
}

// Update Chart
function updatePredictionChart(actualData, result, years, algorithmName) {
    const ctx = document.getElementById('prediction-chart');
    if (!ctx) return;

    const lastYear = parseInt(years[years.length - 1] || new Date().getFullYear());
    const futureYears = Array.from({ length: 5 }, (_, i) => (lastYear + 1 + i).toString());
    const allLabels = [...years, ...futureYears];

    const fittedData = result.fitted || result.smoothed || [];
    const predictions = result.predictions || [];

    // Prepare datasets
    const actualDataset = [...actualData, ...Array(5).fill(null)];
    const fittedDataset = [...fittedData, ...Array(5).fill(null)];
    const predictionDataset = [...Array(actualData.length).fill(null), ...predictions];

    if (predictionChart) {
        predictionChart.destroy();
    }

    predictionChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: allLabels,
            datasets: [
                {
                    label: 'Data Aktual',
                    data: actualDataset,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.2,
                    fill: false,
                    pointRadius: 5
                },
                {
                    label: algorithmName,
                    data: fittedDataset,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.2,
                    fill: false,
                    borderDash: [5, 5],
                    pointRadius: 3
                },
                {
                    label: 'Prediksi Tahunan',
                    data: predictionDataset,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.2)',
                    tension: 0.2,
                    fill: true,
                    pointRadius: 6,
                    pointStyle: 'rectRot'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${context.parsed.y} unit`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Jumlah Unit' }
                },
                x: {
                    title: { display: true, text: 'Tahun' }
                }
            }
        }
    });
}

// Update Stats with Animation
function updatePredictionStats(actualData, result) {
    const predictions = result.predictions || [];

    // Average prediction
    const avgPrediction = predictions.length > 0
        ? Math.round(predictions.reduce((a, b) => a + b) / predictions.length)
        : 0;

    // Growth rate
    const lastActual = actualData[actualData.length - 1];
    const avgFuture = avgPrediction;
    const growthRate = lastActual > 0
        ? Math.round(((avgFuture - lastActual) / lastActual) * 100)
        : 0;

    // Recommended stock (Yearly)
    const maxPrediction = Math.max(...predictions);
    const recommendedStock = Math.round(maxPrediction * 1.1); // 10% buffer for yearly

    // Update UI with animation
    animateCounter('avg-prediction', avgPrediction);
    animateCounter('recommended-stock', recommendedStock);

    const growthEl = document.getElementById('growth-rate');
    if (growthEl) {
        growthEl.textContent = `${growthRate > 0 ? '+' : ''}${growthRate}%`;
        growthEl.className = `trend ${growthRate >= 0 ? 'up' : 'down'}`;
        growthEl.style.animation = 'none';
        growthEl.offsetHeight; // Trigger reflow
        growthEl.style.animation = 'countUp 0.5s ease forwards';
    }

    // Update labels in HTML to say "Tahun" instead of "Bulan"
    // Note: This assumes specific HTML structure exists
    const trendLabel = document.querySelector('.trend.up:not(#growth-rate)');
    if (trendLabel && trendLabel.textContent.includes('bulan')) trendLabel.textContent = 'Per Tahun';
}

// Animated Counter Function
function animateCounter(elementId, targetValue, duration = 1000) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const startValue = parseInt(element.textContent.replace(/[^\d]/g, '')) || 0;
    const startTime = performance.now();

    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Easing function (ease-out-cubic)
        const easeOut = 1 - Math.pow(1 - progress, 3);
        const currentValue = Math.round(startValue + (targetValue - startValue) * easeOut);

        element.textContent = formatNumber(currentValue);

        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        }
    }

    // Add animation class
    element.classList.add('animate-number');
    requestAnimationFrame(updateCounter);
}

// Update Recommendations
function updateRecommendations(predictions, category, years) {
    const container = document.getElementById('recommendations');
    if (!container) return;

    const avg = predictions.reduce((a, b) => a + b) / predictions.length;
    const max = Math.max(...predictions);
    const categoryName = SinarJayaData.categories.find(c => c.id === category)?.name || category;

    const lastYear = parseInt(years[years.length - 1] || new Date().getFullYear());

    container.innerHTML = `
    <div class="alert info">
      <i class="fas fa-lightbulb"></i>
      <div>
        <strong>Rekomendasi Stok Tahunan ${categoryName}</strong>
        <p style="margin: 0.5rem 0 0;">
          Berdasarkan prediksi jangka panjang, disarankan untuk merencanakan kapasitas produksi minimal <strong>${formatNumber(Math.round(max * 1.05))}</strong> unit 
          untuk 5 tahun ke depan, dengan rata-rata kebutuhan <strong>${formatNumber(Math.round(avg))}</strong> unit per tahun.
        </p>
      </div>
    </div>
    <div style="margin-top: 1rem;">
      <h6 style="margin-bottom: 0.75rem;">Detail Prediksi Tahunan:</h6>
      <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        ${predictions.map((p, i) => `
          <div style="background: var(--gray-50); padding: 0.75rem; border-radius: var(--radius); text-align: center; flex: 1; min-width: 100px;">
            <span style="font-size: 0.75rem; color: var(--gray-500);">Tahun ${lastYear + 1 + i}</span>
            <strong style="display: block; color: var(--primary); font-size: 1.1rem;">${formatNumber(p)}</strong>
          </div>
        `).join('')}
      </div>
    </div>
  `;
}

// New Function: Update Table specifically for Yearly Data
function updateYearlyTable(years, values, category) {
    const container = document.getElementById('stock-history-table');
    if (!container) return;

    const categoryName = SinarJayaData.categories.find(c => c.id === category)?.name || category;

    container.innerHTML = `
    <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Tahun</th>
          <th>Total ${categoryName}</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        ${years.map((year, i) => {
        const val = values[i];
        const prevVal = i > 0 ? values[i - 1] : val;
        const growth = prevVal > 0 ? ((val - prevVal) / prevVal * 100).toFixed(1) : 0;
        const trendIcon = growth >= 0 ? '<i class="fas fa-arrow-up text-success"></i>' : '<i class="fas fa-arrow-down text-danger"></i>';
        return `
          <tr>
            <td style="font-weight: 600;">${year}</td>
            <td>${formatNumber(val)} unit</td>
            <td>${i === 0 ? '-' : trendIcon + ' ' + Math.abs(growth) + '%'}</td>
          </tr>
        `}).join('')}
      </tbody>
    </table>
    </div>
  `;
}

// Export to PDF
function exportPredictionPDF() {
    try {
        showToast('Menyiapkan laporan PDF...', 'info');

        const stockHistory = getHistoricalData();
        const category = document.getElementById('prediction-category')?.value || 'kemeja';
        const categoryName = SinarJayaData.categories.find(c => c.id === category)?.name || category;

        // Gunakan data tahunan (sama dengan grafik)
        let { years, values } = processDataYearly(stockHistory, category);

        // Fallback dummy data jika kosong
        if (years.length === 0) {
            const cy = new Date().getFullYear();
            years = [cy - 4, cy - 3, cy - 2, cy - 1, cy].map(String);
            const base = category === 'kemeja' ? 1200 : category === 'polo' ? 2000 : category === 'jaket' ? 800 : 3000;
            values = years.map((_, i) => Math.round(base * (1 + i * 0.1)));
        }

        let result, algorithmName;
        switch (currentAlgorithm) {
            case 'moving-average':
                result = movingAverage(values); algorithmName = 'Moving Average'; break;
            case 'linear-regression':
                result = linearRegression(values); algorithmName = 'Linear Regression'; break;
            case 'trend-analysis':
                result = trendAnalysis(values); algorithmName = 'Trend Analysis'; break;
            default:
                result = movingAverage(values); algorithmName = 'Moving Average';
        }

        const predictions = result.predictions || [];
        const avgPrediction = predictions.length > 0 ? Math.round(predictions.reduce((a, b) => a + b, 0) / predictions.length) : 0;
        const maxPrediction = predictions.length > 0 ? Math.max(...predictions) : 0;
        const lastActual = values[values.length - 1] || 0;
        const growthRate = lastActual > 0 ? Math.round(((avgPrediction - lastActual) / lastActual) * 100) : 0;
        const lastYear = parseInt(years[years.length - 1] || new Date().getFullYear());

        const printWindow = window.open('', '_blank', 'width=900,height=700');
        if (!printWindow || printWindow.closed) {
            showToast('Pop-up diblokir! Izinkan pop-up di browser Anda.', 'error');
            alert('Pop-up diblokir!\n\nCara mengizinkan:\n1. Klik ikon blokir pop-up di address bar\n2. Pilih "Selalu izinkan"\n3. Coba lagi');
            return;
        }

        const printContent = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Prediksi Stok - SINAR JAYA KONVEKSI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 40px; color: #333; font-size: 14px; }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 3px solid #ff6b00; }
        .logo { font-size: 26px; font-weight: bold; color: #0a0f1a; }
        .logo span { color: #ff6b00; }
        .subtitle { color: #666; font-size: 13px; margin-top: 4px; }
        .report-title { font-size: 20px; font-weight: bold; margin: 20px 0 6px; color: #0a0f1a; text-align: center; }
        .badge { display: inline-block; background: linear-gradient(135deg, #ff6b00, #ffd700); color: #0a0f1a; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .date { font-size: 12px; color: #888; margin: 8px 0 20px; text-align: center; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 25px; }
        .stat-box { padding: 14px; border-radius: 8px; text-align: center; border-left: 4px solid #3b82f6; background: #f0f9ff; }
        .stat-box.warning { border-left-color: #ff6b00; background: #fff7ed; }
        .stat-box.success { border-left-color: #10b981; background: #f0fdf4; }
        .stat-label { font-size: 12px; color: #666; margin-bottom: 4px; }
        .stat-value { font-size: 22px; font-weight: bold; color: #0a0f1a; }
        h3 { font-size: 15px; font-weight: 600; margin: 20px 0 10px; color: #0a0f1a; border-left: 3px solid #ff6b00; padding-left: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px 12px; text-align: center; }
        th { background: #0a0f1a; color: white; font-weight: 600; font-size: 13px; }
        tr:nth-child(even) { background: #f8f9fa; }
        .pred-row { background: #fff7ed !important; }
        .pred-row td { color: #ff6b00; font-weight: 600; }
        .recommendation { margin-top: 20px; padding: 16px 20px; background: linear-gradient(135deg, #f0f9ff, #e0f2fe); border-radius: 8px; border-left: 4px solid #3b82f6; }
        .recommendation h3 { border: none; padding: 0; margin-bottom: 8px; }
        .footer { margin-top: 30px; text-align: center; font-size: 11px; color: #aaa; border-top: 1px solid #eee; padding-top: 16px; }
        .print-btn { text-align: center; margin-top: 20px; }
        .print-btn button { padding: 12px 32px; font-size: 15px; cursor: pointer; background: linear-gradient(135deg, #ff6b00, #ffd700); color: #0a0f1a; border: none; border-radius: 8px; font-weight: bold; }
        .print-btn p { margin-top: 8px; color: #666; font-size: 12px; }
        @media print { .print-btn { display: none; } body { padding: 20px; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">SINAR JAYA <span>KONVEKSI</span></div>
        <div class="subtitle">Desa Padurenan, Kecamatan Gebog, Kabupaten Kudus, Jawa Tengah</div>
        <div class="subtitle">WhatsApp: +62 856 4735 2998 | Email: info@sinarjaya-konveksi.com</div>
    </div>

    <div class="report-title">Laporan Prediksi Stok Tahunan - ${categoryName}</div>
    <div style="text-align:center;"><span class="badge">Algoritma: ${algorithmName}</span></div>
    <div class="date">Dicetak: ${new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</div>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-label">Rata-rata Prediksi/Tahun</div>
            <div class="stat-value">${avgPrediction.toLocaleString('id-ID')}</div>
        </div>
        <div class="stat-box warning">
            <div class="stat-label">Prediksi Tertinggi</div>
            <div class="stat-value">${maxPrediction.toLocaleString('id-ID')}</div>
        </div>
        <div class="stat-box success">
            <div class="stat-label">Pertumbuhan vs Tahun Terakhir</div>
            <div class="stat-value">${growthRate > 0 ? '+' : ''}${growthRate}%</div>
        </div>
    </div>

    <h3>Data Historis Tahunan</h3>
    <table>
        <thead><tr><th>Tahun</th><th>Total ${categoryName} (Unit)</th><th>Perubahan</th></tr></thead>
        <tbody>
            ${years.map((yr, i) => {
            const prev = i > 0 ? values[i - 1] : values[i];
            const change = prev > 0 ? ((values[i] - prev) / prev * 100).toFixed(1) : '0.0';
            return `<tr>
                    <td><strong>${yr}</strong></td>
                    <td>${values[i].toLocaleString('id-ID')}</td>
                    <td>${i === 0 ? '-' : (parseFloat(change) >= 0 ? '▲ +' : '▼ ') + Math.abs(change) + '%'}</td>
                </tr>`;
        }).join('')}
        </tbody>
    </table>

    <h3>Prediksi 5 Tahun Ke Depan</h3>
    <table>
        <thead><tr><th>Tahun</th><th>Prediksi Stok</th><th>Rekomendasi Stok (+10% Buffer)</th></tr></thead>
        <tbody>
            ${predictions.map((p, i) => `
                <tr class="pred-row">
                    <td><strong>${lastYear + 1 + i}</strong></td>
                    <td><strong>${p.toLocaleString('id-ID')}</strong></td>
                    <td>${Math.round(p * 1.1).toLocaleString('id-ID')}</td>
                </tr>
            `).join('')}
        </tbody>
    </table>

    <div class="recommendation">
        <h3>💡 Rekomendasi</h3>
        <p>Berdasarkan analisis prediksi menggunakan algoritma <strong>${algorithmName}</strong>,
        disarankan untuk merencanakan kapasitas produksi <strong>${categoryName}</strong> minimal
        <strong>${Math.round(maxPrediction * 1.1).toLocaleString('id-ID')}</strong> unit per tahun
        selama 5 tahun ke depan, dengan rata-rata kebutuhan <strong>${avgPrediction.toLocaleString('id-ID')}</strong> unit per tahun.</p>
    </div>

    <div class="footer">
        <p>Dokumen ini dicetak otomatis oleh Sistem Prediksi SINAR JAYA KONVEKSI</p>
        <p>© ${new Date().getFullYear()} SINAR JAYA KONVEKSI - Semua Hak Dilindungi</p>
    </div>
    <div class="print-btn">
        <button onclick="window.print()">🖨️ Cetak / Simpan sebagai PDF</button>
        <p>Pilih "Save as PDF" pada dialog cetak untuk menyimpan file PDF</p>
    </div>
</body>
</html>`;

        printWindow.document.write(printContent);
        printWindow.document.close();
        setTimeout(() => showToast('Halaman cetak siap. Pilih "Save as PDF" untuk menyimpan.', 'success'), 500);

    } catch (error) {
        console.error('Error exporting PDF:', error);
        showToast('Gagal ekspor PDF: ' + error.message, 'error');
    }
}
// Export to Excel (CSV)
function exportPredictionExcel() {
    try {
        showToast('Menyiapkan file Excel...', 'info');

        const stockHistory = getHistoricalData();
        const category = document.getElementById('prediction-category')?.value || 'kemeja';
        const categoryName = SinarJayaData.categories.find(c => c.id === category)?.name || category;

        // Gunakan data tahunan (sama dengan grafik)
        let { years, values } = processDataYearly(stockHistory, category);

        if (years.length === 0) {
            const cy = new Date().getFullYear();
            years = [cy - 4, cy - 3, cy - 2, cy - 1, cy].map(String);
            const base = category === 'kemeja' ? 1200 : category === 'polo' ? 2000 : category === 'jaket' ? 800 : 3000;
            values = years.map((_, i) => Math.round(base * (1 + i * 0.1)));
        }

        let result, algorithmName;
        switch (currentAlgorithm) {
            case 'moving-average':
                result = movingAverage(values); algorithmName = 'Moving Average'; break;
            case 'linear-regression':
                result = linearRegression(values); algorithmName = 'Linear Regression'; break;
            case 'trend-analysis':
                result = trendAnalysis(values); algorithmName = 'Trend Analysis'; break;
            default:
                result = movingAverage(values); algorithmName = 'Moving Average';
        }

        const predictions = result.predictions || [];
        const avgPrediction = predictions.length > 0 ? Math.round(predictions.reduce((a, b) => a + b, 0) / predictions.length) : 0;
        const maxPrediction = predictions.length > 0 ? Math.max(...predictions) : 0;
        const lastYear = parseInt(years[years.length - 1] || new Date().getFullYear());

        // ===== Buat CSV =====
        let csv = '\uFEFF'; // BOM UTF-8

        // Header
        csv += `"LAPORAN PREDIKSI STOK TAHUNAN - SINAR JAYA KONVEKSI"\n`;
        csv += `"Kategori: ${categoryName}"\n`;
        csv += `"Algoritma: ${algorithmName}"\n`;
        csv += `"Tanggal Export: ${new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}"\n\n`;

        // Data historis tahunan
        csv += `"DATA HISTORIS TAHUNAN"\n`;
        csv += `"Tahun","Total ${categoryName} (Unit)","Perubahan (%)"\n`;
        years.forEach((yr, i) => {
            const prev = i > 0 ? values[i - 1] : values[i];
            const change = prev > 0 ? ((values[i] - prev) / prev * 100).toFixed(1) : '0.0';
            csv += `"${yr}","${values[i]}","${i === 0 ? '-' : (parseFloat(change) >= 0 ? '+' : '') + change + '%'}"\n`;
        });

        // Prediksi 5 tahun
        csv += `\n"PREDIKSI 5 TAHUN KE DEPAN"\n`;
        csv += `"Tahun","Prediksi Stok","Rekomendasi (+10% Buffer)"\n`;
        predictions.forEach((p, i) => {
            csv += `"${lastYear + 1 + i}","${p}","${Math.round(p * 1.1)}"\n`;
        });

        // Ringkasan
        csv += `\n"RINGKASAN"\n`;
        csv += `"Rata-rata Prediksi/Tahun","${avgPrediction}"\n`;
        csv += `"Prediksi Tertinggi","${maxPrediction}"\n`;
        csv += `"Rekomendasi Stok Tahunan","${Math.round(maxPrediction * 1.1)}"\n`;

        // Semua kategori per tahun
        csv += `\n"DATA HISTORIS SEMUA KATEGORI (TAHUNAN)"\n`;
        csv += `"Tahun","Kemeja","Polo Shirt","Jaket","Seragam","Kaos","Celana"\n`;

        const allCategories = ['kemeja', 'polo', 'jaket', 'seragam', 'kaos', 'celana'];
        // Kumpulkan data per tahun untuk semua kategori
        const allYearsSet = new Set();
        allCategories.forEach(cat => {
            const { years: catYears } = processDataYearly(stockHistory, cat);
            catYears.forEach(y => allYearsSet.add(y));
        });
        const allYears = Array.from(allYearsSet).sort();

        allYears.forEach(yr => {
            const row = [yr];
            allCategories.forEach(cat => {
                const { years: catYears, values: catValues } = processDataYearly(stockHistory, cat);
                const idx = catYears.indexOf(yr);
                row.push(idx >= 0 ? catValues[idx] : 0);
            });
            csv += row.map(v => `"${v}"`).join(',') + '\n';
        });

        // Download
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const fileName = `Prediksi_Stok_${categoryName}_${new Date().toISOString().slice(0, 10)}.csv`;
        link.href = URL.createObjectURL(blob);
        link.download = fileName;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);

        showToast(`File ${fileName} berhasil diunduh!`, 'success');

    } catch (error) {
        console.error('Error exporting Excel:', error);
        showToast('Gagal ekspor Excel: ' + error.message, 'error');
    }
}

// Export functions
window.runPrediction = runPrediction;
window.exportPredictionPDF = exportPredictionPDF;
window.exportPredictionExcel = exportPredictionExcel;
