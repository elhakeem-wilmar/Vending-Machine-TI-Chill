const express = require('express');
const cors = require('cors');
const path = require('path');
const fs = require('fs');
const os = require('os');
const dotenv = require('dotenv');
const midtransClient = require('midtrans-client');

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;
const isProduction = process.env.IS_PRODUCTION === 'true';
const ADMIN_PIN = process.env.ADMIN_PIN || '1234';

app.use(cors());
app.use(express.json());

// Serve static frontend files
app.use(express.static(path.join(__dirname, 'frontend')));

// Admin route
app.get('/admin', (req, res) => {
  res.sendFile(path.join(__dirname, 'frontend', 'admin.html'));
});

// ── Data Store Helper (Zero-Database JSON Storage) ──
const DATA_PATH = path.join(__dirname, 'data', 'store.json');

function getStore() {
  try {
    if (!fs.existsSync(DATA_PATH)) {
      const initial = {
        products: [
          { id: "MK01", name: "Kopi Kotak", category: "Kopi", price: 5000, stock: 8, volume: "250 ml", temp: "Dingin ❄️", emoji: "☕" },
          { id: "MK02", name: "Susu Coklat Kotak", category: "Susu", price: 6000, stock: 6, volume: "200 ml", temp: "Dingin ❄️", emoji: "🍫" },
          { id: "MK03", name: "Susu UHT Kotak", category: "Susu", price: 6000, stock: 5, volume: "200 ml", temp: "Dingin ❄️", emoji: "🥛" },
          { id: "MK04", name: "Teh Kotak", category: "Teh", price: 4000, stock: 10, volume: "300 ml", temp: "Dingin ❄️", emoji: "🍵" },
        ],
        transactions: []
      };
      fs.mkdirSync(path.dirname(DATA_PATH), { recursive: true });
      fs.writeFileSync(DATA_PATH, JSON.stringify(initial, null, 2), 'utf-8');
      return initial;
    }
    return JSON.parse(fs.readFileSync(DATA_PATH, 'utf-8'));
  } catch (err) {
    console.error('Error reading store:', err);
    return { products: [], transactions: [] };
  }
}

function saveStore(data) {
  try {
    fs.writeFileSync(DATA_PATH, JSON.stringify(data, null, 2), 'utf-8');
  } catch (err) {
    console.error('Error saving store:', err);
  }
}

// ── Public Endpoints ──

// Config endpoint untuk frontend
app.get('/api/config', (req, res) => {
  const clientKey = process.env.MIDTRANS_CLIENT_KEY || '';
  const snapUrl = isProduction
    ? 'https://app.midtrans.com/snap/snap.js'
    : 'https://app.sandbox.midtrans.com/snap/snap.js';

  res.json({
    isProduction,
    clientKey,
    snapUrl,
  });
});

// Ambil katalog produk dengan live stock
app.get('/api/products', (req, res) => {
  const store = getStore();
  res.json(store.products);
});

// Endpoint membuat Snap Token transaksi (validasi stok server-side)
app.post('/api/payment/token', async (req, res) => {
  try {
    const { orderId, items, grossAmount } = req.body;

    const serverKey = process.env.MIDTRANS_SERVER_KEY;
    const clientKey = process.env.MIDTRANS_CLIENT_KEY;

    if (!serverKey || serverKey.includes('MASUKKAN_SERVER_KEY')) {
      return res.status(400).json({
        error: 'MIDTRANS_SERVER_KEY belum diisi di file .env. Silakan masukkan Server Key dari dashboard Midtrans Sandbox terlebih dahulu.',
      });
    }

    // Validasi aturan: maksimal 1 minuman per transaksi
    const totalQty = items ? items.reduce((sum, i) => sum + Number(i.qty || 1), 0) : 1;
    if (totalQty > 1 || (items && items.length > 1)) {
      return res.status(400).json({
        error: 'Vending machine hanya dapat memproses 1 minuman per transaksi.',
      });
    }

    // Validasi stok di server
    const store = getStore();
    if (items && items.length > 0) {
      for (const item of items) {
        const prod = store.products.find(p => p.id === item.id);
        if (!prod || prod.stock < item.qty) {
          return res.status(400).json({
            error: `Stok untuk "${item.name || 'Produk'}" tidak mencukupi (Tersisa: ${prod ? prod.stock : 0}).`,
          });
        }
      }
    }

    const snap = new midtransClient.Snap({
      isProduction,
      serverKey,
      clientKey,
    });

    const itemDetails = (items && items.length > 0)
      ? items.map(item => ({
          id: String(item.id || 'ITEM'),
          price: Math.round(Number(item.price)),
          quantity: Number(item.qty || 1),
          name: String(item.name || 'Produk').slice(0, 50),
        }))
      : [{
          id: 'SVM-ORDER',
          price: Math.round(Number(grossAmount)),
          quantity: 1,
          name: 'Pesanan Smart Vending',
        }];

    const host = req.get('host');
    const protocol = req.protocol || 'http';
    const finalOrderId = orderId || `SVM-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
    const returnUrl = `${protocol}://${host}/?order_id=${finalOrderId}&finish=true`;

    // Simpan order tertunda ke database server
    store.pendingOrders = store.pendingOrders || {};
    store.pendingOrders[finalOrderId] = {
      orderId: finalOrderId,
      items: itemDetails,
      grossAmount: Math.round(Number(grossAmount)),
      createdAt: new Date().toISOString(),
    };
    saveStore(store);

    const parameter = {
      transaction_details: {
        order_id: finalOrderId,
        gross_amount: Math.round(Number(grossAmount)),
      },
      item_details: itemDetails,
      customer_details: {
        first_name: 'Pelanggan Vending TI-Chill',
      },
      // URL redirect otomatis kembali ke kiosk setelah bayar di HP / simulator
      callbacks: {
        finish: returnUrl,
      },
      gopay: {
        enable_callback: true,
        callback_url: returnUrl,
      },
      shopeepay: {
        callback_url: returnUrl,
      },
      // Metode pembayaran online instan: QRIS, E-Wallet & Virtual Account (Tanpa Kartu Kredit & Tanpa Cash/Minimarket)
      enabled_payments: [
        'other_qris',
        'qris',
        'gopay',
        'shopeepay',
        'bca_va',
        'echannel',
        'bni_va',
        'bri_va',
        'cimb_va',
        'permata_va',
        'other_va',
      ],
    };

    const transaction = await snap.createTransaction(parameter);
    res.json({
      token: transaction.token,
      redirect_url: transaction.redirect_url,
    });
  } catch (error) {
    console.error('Midtrans Error:', error.message || error);
    res.status(500).json({
      error: error.message || 'Gagal membuat transaksi Midtrans',
    });
  }
});

// ── Integrasi Hardware ESP32 Servo Vending Machine ──
const ESP32_IP = process.env.ESP32_IP || 'http://10.108.4.168';

// Pemetaan ID Produk ke nomor Slot Servo pada ESP32
const SLOT_MAP = {
  'MK01': 1, // Kopi Kotak -> Servo 1
  'MK02': 2, // Susu Coklat Kotak -> Servo 2
  'MK03': 3, // Susu UHT Kotak -> Servo 3
  'MK04': 4, // Teh Kotak -> Servo 4
};

// Fungsi HTTP untuk memicu putaran servo ESP32
async function triggerVendingMachine(slot = 1) {
  const baseIp = (process.env.ESP32_IP || ESP32_IP).replace(/\/+$/, '');
  const url = `${baseIp}/api/servo?servo=${slot}`;
  console.log(`[Hardware] Mengirim perintah putar servo slot ${slot}: ${url}`);

  try {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 6000);
    const res = await fetch(url, { signal: controller.signal });
    clearTimeout(timeout);

    if (res.ok) {
      console.log(`[Hardware] Sukses! Servo slot ${slot} telah berputar.`);
      return { success: true, slot, url };
    } else {
      console.warn(`[Hardware] ESP32 merespon status HTTP ${res.status}`);
      return { success: false, slot, status: res.status };
    }
  } catch (err) {
    console.error(`[Hardware] Gagal menghubungi ESP32 di ${baseIp}. Pastikan mesin menyala dan IP benar:`, err.message);
    return { success: false, slot, error: err.message };
  }
}

// Endpoint uji coba servo manual dari Dashboard Admin / CLI
app.post('/api/hardware/test', async (req, res) => {
  const slot = Number(req.body.slot) || 1;
  const result = await triggerVendingMachine(slot);
  res.json(result);
});

// Selesaikan transaksi: kurangi stok dan simpan ke riwayat transaksi
app.post('/api/payment/complete', (req, res) => {
  try {
    const { orderId, items, grossAmount, paymentType } = req.body;
    const store = getStore();

    // Cek agar tidak duplikat orderId jika sudah tersimpan
    const existing = store.transactions.find(t => t.id === orderId || t.orderId === orderId);
    if (existing) {
      return res.json({ success: true, transaction: existing, products: store.products });
    }

    // Kurangi stok barang
    if (items && Array.isArray(items)) {
      items.forEach(cartItem => {
        const prod = store.products.find(p => p.id === cartItem.id);
        if (prod) {
          prod.stock = Math.max(0, prod.stock - cartItem.qty);
        }
      });
    }

    // Catat transaksi baru
    const newTransaction = {
      id: orderId || `TRX-${Date.now().toString(36).toUpperCase()}`,
      orderId: orderId,
      items: items || [],
      total: Number(grossAmount) || 0,
      paymentType: paymentType || 'QRIS / E-Wallet',
      status: 'Lunas',
      timestamp: new Date().toISOString(),
    };

    store.transactions.unshift(newTransaction);
    saveStore(store);

    // Gerakkan servo ESP32 sesuai item yang dibeli
    const boughtItem = (items && items[0]) || {};
    const servoSlot = SLOT_MAP[boughtItem.id] || 1;
    triggerVendingMachine(servoSlot);

    res.json({ success: true, transaction: newTransaction, products: store.products });
  } catch (err) {
    console.error('Error completing transaction:', err);
    res.status(500).json({ error: 'Gagal mencatat transaksi' });
  }
});

// Cek status transaksi langsung ke Midtrans API (Fallback jika pop-up ditutup tapi sudah dibayar)
app.get('/api/payment/check-status/:orderId', async (req, res) => {
  try {
    const { orderId } = req.params;
    const store = getStore();

    // Jika sudah tercatat di store
    const existing = store.transactions.find(t => t.id === orderId || t.orderId === orderId);
    if (existing) {
      return res.json({ settled: true, transaction: existing });
    }

    const snap = new midtransClient.Snap({
      isProduction,
      serverKey: process.env.MIDTRANS_SERVER_KEY,
      clientKey: process.env.MIDTRANS_CLIENT_KEY,
    });

    const statusRes = await snap.transaction.status(orderId);
    const isSettled = statusRes.transaction_status === 'settlement' || statusRes.transaction_status === 'capture';

    if (isSettled) {
      // Ambil rincian pesanan dari pendingOrders jika tersedia
      const pending = (store.pendingOrders && store.pendingOrders[orderId]) || {};
      const orderItems = pending.items || [];

      // Kurangi stok produk jika belum terkurangi
      if (orderItems.length > 0) {
        orderItems.forEach(cartItem => {
          const prod = store.products.find(p => p.id === cartItem.id);
          if (prod) {
            prod.stock = Math.max(0, prod.stock - (cartItem.quantity || cartItem.qty || 1));
          }
        });
      }

      const newTransaction = {
        id: orderId,
        orderId: orderId,
        items: orderItems.map(i => ({
          id: i.id,
          name: i.name,
          qty: i.quantity || i.qty || 1,
          price: i.price,
        })),
        total: Number(statusRes.gross_amount) || pending.grossAmount || 0,
        paymentType: statusRes.payment_type || 'Online',
        status: 'Lunas',
        timestamp: new Date().toISOString(),
      };

      store.transactions.unshift(newTransaction);
      if (store.pendingOrders) delete store.pendingOrders[orderId];
      saveStore(store);

      // Gerakkan servo ESP32 sesuai item yang dibeli
      const boughtItem = (orderItems && orderItems[0]) || {};
      const servoSlot = SLOT_MAP[boughtItem.id] || 1;
      triggerVendingMachine(servoSlot);

      return res.json({ settled: true, transaction: newTransaction, products: store.products });
    }

    res.json({ settled: false, status: statusRes.transaction_status });
  } catch (err) {
    res.json({ settled: false, error: err.message });
  }
});

// ── Admin Endpoints (Dilindungi PIN) ──

function verifyAdminPin(req, res, next) {
  const pin = req.headers['x-admin-pin'] || req.body.pin || req.query.pin;
  if (pin !== ADMIN_PIN) {
    return res.status(401).json({ error: 'PIN Admin tidak valid' });
  }
  next();
}

// Verifikasi PIN Login Admin
app.post('/api/admin/login', (req, res) => {
  const { pin } = req.body;
  if (pin === ADMIN_PIN) {
    res.json({ success: true, message: 'Autentikasi admin berhasil' });
  } else {
    res.status(401).json({ error: 'PIN yang dimasukkan salah!' });
  }
});

// Ambil seluruh data admin (Ringkasan, Inventaris, Riwayat Transaksi)
app.get('/api/admin/data', verifyAdminPin, (req, res) => {
  const store = getStore();

  const totalRevenue = store.transactions.reduce((sum, tx) => sum + (Number(tx.total) || 0), 0);
  const totalTransactions = store.transactions.length;
  const totalItemsSold = store.transactions.reduce((sum, tx) => {
    return sum + (tx.items || []).reduce((itemSum, item) => itemSum + (item.qty || 1), 0);
  }, 0);
  const lowStockCount = store.products.filter(p => p.stock <= 2).length;

  res.json({
    stats: {
      totalRevenue,
      totalTransactions,
      totalItemsSold,
      lowStockCount,
    },
    products: store.products,
    transactions: store.transactions,
  });
});

// Update Stok Barang (Restock)
app.post('/api/admin/update-stock', verifyAdminPin, (req, res) => {
  const { productId, delta, newStock } = req.body;
  const store = getStore();
  const prod = store.products.find(p => p.id === productId);

  if (!prod) {
    return res.status(404).json({ error: 'Produk tidak ditemukan' });
  }

  if (typeof newStock === 'number') {
    prod.stock = Math.max(0, Math.round(newStock));
  } else if (typeof delta === 'number') {
    prod.stock = Math.max(0, prod.stock + delta);
  }

  saveStore(store);
  res.json({ success: true, product: prod, products: store.products });
});

// Update Harga Barang
app.post('/api/admin/update-price', verifyAdminPin, (req, res) => {
  const { productId, newPrice } = req.body;
  const store = getStore();
  const prod = store.products.find(p => p.id === productId);

  if (!prod) {
    return res.status(404).json({ error: 'Produk tidak ditemukan' });
  }

  const priceNum = Number(newPrice);
  if (isNaN(priceNum) || priceNum <= 0) {
    return res.status(400).json({ error: 'Nominal harga tidak valid' });
  }

  prod.price = Math.round(priceNum);
  saveStore(store);
  res.json({ success: true, product: prod, products: store.products });
});

// Dapatkan IP lokal WiFi mesin saat ini untuk pembeli scan QR
app.get('/api/admin/network-ip', verifyAdminPin, (req, res) => {
  const nets = os.networkInterfaces();
  let localIp = 'localhost';

  for (const name of Object.keys(nets)) {
    for (const net of nets[name]) {
      if (net.family === 'IPv4' && !net.internal) {
        localIp = net.address;
        break;
      }
    }
    if (localIp !== 'localhost') break;
  }

  res.json({
    localIp,
    port: PORT,
    localUrl: `http://${localIp}:${PORT}`
  });
});

app.listen(PORT, () => {
  console.log(`\n======================================================`);
  console.log(` Smart Vending Machine Server Berjalan! 🥤`);
  console.log(` Mode: ${isProduction ? 'PRODUCTION 🚀' : 'SANDBOX 🧪'}`);
  console.log(` Web Pelanggan : http://localhost:${PORT}`);
  console.log(` Dashboard Admin: http://localhost:${PORT}/admin (PIN: ${ADMIN_PIN})`);
  console.log(`======================================================\n`);
});
