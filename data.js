// Sample Data for SINAR JAYA KONVEKSI
// Data ini harus sinkron dengan database sinar_jaya_konveksi.sql
const SinarJayaData = {
  // Company Info
  company: {
    name: 'SINAR JAYA KONVEKSI',
    tagline: 'Produsen Pakaian Berkualitas',
    description: 'SINAR JAYA KONVEKSI didirikan pada tahun 2017 oleh dua bersaudara, Bapak Muhammad Salim dan Bapak Muhammad Salman Alfarisi. Kami melayani berbagai kebutuhan konveksi mulai dari kemeja, polo shirt, jaket, hingga seragam perusahaan.',
    founded: 2017,
    founders: 'Muhammad Salim dan Muhammad Salman Alfarisi',
    employees: 32,
    address: 'Desa Padurenan, Kecamatan Gebog, Kabupaten Kudus, Jawa Tengah',
    city: 'Kudus',
    province: 'Jawa Tengah',
    postalCode: '59354',
    phone: '+62 856 4735 2998',
    whatsapp: '6285647352998',
    email: 'info@sinarjaya-konveksi.com',
    operationalHours: 'Senin - Sabtu: 08:00 - 17:00, Minggu: Tutup',
    social: {
      facebook: 'https://www.facebook.com/share/1BrUVgcHFc/',
      instagram: 'https://www.instagram.com/sinar_jaya_konveksi_kudus',
      shopee: 'https://s.shopee.co.id/10xHFKtWol',
      tiktok: '',
      youtube: ''
    }
  },

  // Statistics
  stats: {
    yearsFounded: 9, // Since 2017
    totalProducts: 0,
    happyCustomers: 0,
    projectsCompleted: 0
  },


  // Product Categories
  categories: [
    { id: 'kemeja', name: 'Kemeja', icon: 'fa-shirt' },
    { id: 'polo', name: 'Polo Shirt', icon: 'fa-tshirt' },
    { id: 'jaket', name: 'Jaket', icon: 'fa-vest' },
    { id: 'seragam', name: 'Seragam', icon: 'fa-user-tie' },
    { id: 'kaos', name: 'Kaos', icon: 'fa-shirt' },
    { id: 'celana', name: 'Celana', icon: 'fa-socks' }
  ],

  // Products - KOSONG (Tambahkan produk asli Anda melalui Admin Panel)
  products: [],

  // Gallery Items
  gallery: [
    {
      id: 1,
      title: 'Proses Penjahitan Kemeja',
      category: 'produksi',
      description: 'Tenaga ahli kami sedang mengerjakan pesanan kemeja seragam dengan ketelitian tinggi.',
      image: 'https://images.unsplash.com/photo-1558171813-4c088753af8f?w=600',
      date: 'Januari 2026'
    },
    {
      id: 2,
      title: 'Koleksi Polo Shirt Premium',
      category: 'produk',
      description: 'Varian warna polo shirt dengan bahan Lacoste Cotton berkualitas.',
      image: 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600',
      date: 'Februari 2026'
    },
    {
      id: 3,
      title: 'Quality Control',
      category: 'produksi',
      description: 'Pengecekan akhir sebelum pengemasan untuk memastikan tidak ada cacat produksi.',
      image: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600',
      date: 'Januari 2026'
    },
    {
      id: 4,
      title: 'Pengiriman Seragam Sekolah',
      category: 'kegiatan',
      description: 'Proses pengiriman 500 pcs seragam ke SMA Negeri 1 Kudus.',
      image: 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=600', // Box/Shipping concept
      date: 'Desember 2025'
    },
    {
      id: 5,
      title: 'Jaket Bomber Custom',
      category: 'produk',
      description: 'Pesanan jaket komunitas motor dengan bordir komputer presisi.',
      image: 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=600',
      date: 'Desember 2025'
    },
    {
      id: 6,
      title: 'Ruang Potong Kain',
      category: 'produksi',
      description: 'Mesin potong modern untuk hasil potongan pola yang akurat dan efisien.',
      image: 'https://images.unsplash.com/photo-1605289982774-9a6fef564df8?w=600', // Textile/Fabric
      date: 'November 2025'
    },
    {
      id: 7,
      title: 'Seragam Kantor Bank Jateng',
      category: 'produk',
      description: 'Desain elegan dan formal untuk kebutuhan seragam harian karyawan.',
      image: 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=600',
      date: 'Februari 2026'
    },
    {
      id: 8,
      title: 'Tim Produksi Sinar Jaya',
      category: 'kegiatan',
      description: 'Kekompakan tim produksi kami adalah kunci kualitas produk Sinar Jaya.',
      image: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=600', // Team
      date: 'Oktober 2025'
    }
  ],

  // Testimonials - Contoh (bisa diedit/hapus)
  testimonials: [],

  // Monthly Sales Data (for prediction) - Clean Start
  salesData: {
    2024: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    2025: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    2026: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
  },

  // Stock history for prediction - No history yet
  stockHistory: []
};


// LocalStorage Management
const Storage = {
  get: (key) => {
    try {
      const data = localStorage.getItem(key);
      return data ? JSON.parse(data) : null;
    } catch (e) {
      console.error('Storage get error:', e);
      return null;
    }
  },

  set: (key, value) => {
    try {
      localStorage.setItem(key, JSON.stringify(value));
      return true;
    } catch (e) {
      console.error('Storage set error:', e);
      return false;
    }
  },

  remove: (key) => {
    try {
      localStorage.removeItem(key);
      return true;
    } catch (e) {
      console.error('Storage remove error:', e);
      return false;
    }
  },

  init: () => {
    // Force reset for Prediction Data to ensure it's cleared from cache
    Storage.set('salesData', SinarJayaData.salesData);
    Storage.set('stockHistory', SinarJayaData.stockHistory);

    // Initialize products if not exists
    if (localStorage.getItem('products') === null) {
      Storage.set('products', SinarJayaData.products);
    }
  },


  reset: () => {
    localStorage.removeItem('products');
    localStorage.removeItem('salesData');
    localStorage.removeItem('stockHistory');
    localStorage.removeItem('gallery');
    localStorage.removeItem('testimonials');
    Storage.init();
    return true;
  }
};


// Initialize storage on load
Storage.init();

// Format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(amount);
}

// Format number with thousand separator
function formatNumber(num) {
  return new Intl.NumberFormat('id-ID').format(num);
}

// Generate unique ID
function generateId() {
  return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

// Show toast notification
function showToast(message, type = 'success') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `
    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"></i>
    <span>${message}</span>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = 'slideIn 0.3s ease reverse';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// Export data and functions
window.SinarJayaData = SinarJayaData;
window.Storage = Storage;
window.formatCurrency = formatCurrency;
window.formatNumber = formatNumber;
window.generateId = generateId;
window.showToast = showToast;
