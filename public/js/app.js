/**
 * STOCKWISE — Inventory Module
 * Core Data Store + Shared Utilities
 * All AJAX calls stubbed for Laravel backend integration
 */

'use strict';
const baseURL=$('#baseUrl').text();
// console.log(baseURL);

/* ================================================================
   CONFIG
   ================================================================ */
const Config = {
  apiBase:      baseURL+ '/api/v1/inventory',
  lowStockThresh: 10,
  currency:       'Rs.',
  dateFormat:     'YYYY-MM-DD',
  itemsPerPage:   10,
};

/* ================================================================
   MOCK DATA STORE (replace with AJAX to Laravel)
   ================================================================ */
const Store = {
  /* ── Categories ── */
  categories: [
  //   { id: 1, name: 'Men\'s Wear',   description: 'Shirts, trousers, formal & casual',  createdAt: '2026-01-10', itemCount: 5 },
  //   { id: 2, name: 'Women\'s Wear', description: 'Dresses, tops, ethnic & casual',      createdAt: '2026-01-10', itemCount: 4 },
  //   { id: 3, name: 'Kids',          description: 'Tees, frocks, boys & girls',          createdAt: '2026-01-12', itemCount: 3 },
  //   { id: 4, name: 'Ethnic',        description: 'Sarees, kurtas, ethnic wear',         createdAt: '2026-01-15', itemCount: 2 },
  //   { id: 5, name: 'Accessories',   description: 'Belts, scarves, bags, caps',          createdAt: '2026-01-20', itemCount: 3 },
  //   { id: 6, name: 'Footwear',      description: 'Sneakers, sandals, formal shoes',     createdAt: '2026-02-01', itemCount: 2 },
  ],
  _catNextId: 7,

  /* ── Items ── */
  items: [
    // { id: 1,  name: 'Oxford Button Shirt',   sku: 'CLT-001', categoryId: 1, brand: 'Arrow',    costPrice: 1200, sellingPrice: 2499, description: 'Classic cotton oxford shirt', emoji: '👔', variants: [
    //     { size: 'S', color: 'White', stock: 18 }, { size: 'M', color: 'White', stock: 24 },
    //     { size: 'L', color: 'White', stock: 14 }, { size: 'XL', color: 'White', stock: 6 },
    //     { size: 'M', color: 'Blue',  stock: 12 }, { size: 'L', color: 'Blue',   stock: 8 },
    // ]},
    // { id: 2,  name: 'Slim Chino Trouser',    sku: 'CLT-002', categoryId: 1, brand: 'Levi\'s', costPrice: 900,  sellingPrice: 1899, description: 'Slim fit stretch chinos', emoji: '👖', variants: [
    //     { size: '30', color: 'Khaki',  stock: 10 }, { size: '32', color: 'Khaki',  stock: 15 },
    //     { size: '34', color: 'Khaki',  stock: 8  }, { size: '32', color: 'Navy',   stock: 4  },
    // ]},
    // { id: 3,  name: 'Floral Midi Dress',     sku: 'CLT-003', categoryId: 2, brand: 'Zara',    costPrice: 1500, sellingPrice: 3299, description: 'Boho floral print midi', emoji: '👗', variants: [
    //     { size: 'XS', color: 'Pink',   stock: 7  }, { size: 'S',  color: 'Pink',   stock: 12 },
    //     { size: 'M',  color: 'Pink',   stock: 9  }, { size: 'L',  color: 'Pink',   stock: 3  },
    // ]},
    // { id: 4,  name: 'Denim Jacket',          sku: 'CLT-004', categoryId: 1, brand: 'Wrangler',costPrice: 2000, sellingPrice: 4199, description: 'Classic blue denim jacket', emoji: '🧥', variants: [
    //     { size: 'S',  color: 'Blue',   stock: 6  }, { size: 'M',  color: 'Blue',   stock: 9  },
    //     { size: 'L',  color: 'Blue',   stock: 2  },
    // ]},
    // { id: 5,  name: 'Knit Pullover',         sku: 'CLT-005', categoryId: 2, brand: 'H&M',     costPrice: 1300, sellingPrice: 2799, description: 'Soft ribbed knit pullover', emoji: '🧶', variants: [
    //     { size: 'S',  color: 'Cream',  stock: 14 }, { size: 'M',  color: 'Cream',  stock: 20 },
    //     { size: 'L',  color: 'Black',  stock: 11 }, { size: 'XL', color: 'Black',  stock: 5  },
    // ]},
    // { id: 6,  name: 'Kids Graphic Tee',      sku: 'CLT-006', categoryId: 3, brand: 'UCB',     costPrice: 350,  sellingPrice:  799, description: 'Cotton graphic print tee', emoji: '👕', variants: [
    //     { size: '4Y', color: 'Red',    stock: 30 }, { size: '6Y', color: 'Red',    stock: 25 },
    //     { size: '8Y', color: 'Blue',   stock: 18 }, { size: '10Y',color: 'Blue',   stock: 12 },
    // ]},
    // { id: 7,  name: 'Leather Belt',          sku: 'CLT-007', categoryId: 5, brand: 'Woodland',costPrice: 350,  sellingPrice:  699, description: 'Genuine leather belt', emoji: '🪢', variants: [
    //     { size: '32', color: 'Brown',  stock: 5  }, { size: '34', color: 'Brown',  stock: 4  },
    //     { size: '36', color: 'Black',  stock: 2  },
    // ]},
    // { id: 8,  name: 'Canvas Sneakers',       sku: 'CLT-008', categoryId: 6, brand: 'Converse',costPrice: 1700, sellingPrice: 3499, description: 'Classic low-top canvas', emoji: '👟', variants: [
    //     { size: '39', color: 'White',  stock: 8  }, { size: '40', color: 'White',  stock: 11 },
    //     { size: '41', color: 'White',  stock: 7  }, { size: '42', color: 'Black',  stock: 9  },
    // ]},
    // { id: 9,  name: 'Silk Saree',            sku: 'CLT-009', categoryId: 4, brand: 'Kota',    costPrice: 2500, sellingPrice: 5999, description: 'Handwoven pure silk saree', emoji: '🥻', variants: [
    //     { size: 'Free', color: 'Red',  stock: 4  }, { size: 'Free', color: 'Green',stock: 3  },
    // ]},
    // { id: 10, name: 'Formal Blazer',         sku: 'CLT-010', categoryId: 1, brand: 'Raymond', costPrice: 3000, sellingPrice: 6499, description: 'Structured formal blazer', emoji: '🤵', variants: [
    //     { size: '38', color: 'Black',  stock: 5  }, { size: '40', color: 'Black',  stock: 7  },
    //     { size: '42', color: 'Navy',   stock: 3  }, { size: '44', color: 'Navy',   stock: 1  },
    // ]},
    // { id: 11, name: 'Crop Top',              sku: 'CLT-011', categoryId: 2, brand: 'H&M',     costPrice: 450,  sellingPrice:  999, description: 'Ribbed cotton crop top', emoji: '👚', variants: [
    //     { size: 'XS', color: 'White',  stock: 22 }, { size: 'S',  color: 'White',  stock: 30 },
    //     { size: 'M',  color: 'Black',  stock: 18 },
    // ]},
    // { id: 12, name: 'Wool Scarf',            sku: 'CLT-012', categoryId: 5, brand: 'Muffin',  costPrice: 600,  sellingPrice: 1299, description: 'Soft merino wool scarf', emoji: '🧣', variants: [
    //     { size: 'Free', color: 'Grey', stock: 3  }, { size: 'Free', color: 'Red',  stock: 1  },
    // ]},
  ],
  _itemNextId: 13,

  /* ── Ledger ── */
  ledger: [
    // { id:1,  date:'2026-03-08', itemId:1, variantKey:'M-White', type:'Purchase',   qty:+24, ref:'PO-001', user:'Admin',    note:'Opening stock' },
    // { id:2,  date:'2026-03-08', itemId:2, variantKey:'32-Khaki',type:'Purchase',   qty:+15, ref:'PO-001', user:'Admin',    note:'Opening stock' },
    // { id:3,  date:'2026-03-08', itemId:3, variantKey:'S-Pink',  type:'Purchase',   qty:+12, ref:'PO-001', user:'Admin',    note:'Opening stock' },
    // { id:4,  date:'2026-03-08', itemId:5, variantKey:'M-Cream', type:'Purchase',   qty:+20, ref:'PO-001', user:'Admin',    note:'Opening stock' },
    // { id:5,  date:'2026-03-09', itemId:1, variantKey:'M-White', type:'Sale',       qty:-2,  ref:'INV-220', user:'Cashier', note:'' },
    // { id:6,  date:'2026-03-09', itemId:3, variantKey:'S-Pink',  type:'Sale',       qty:-3,  ref:'INV-221', user:'Cashier', note:'' },
    // { id:7,  date:'2026-03-09', itemId:7, variantKey:'32-Brown',type:'Adjustment', qty:-1,  ref:'ADJ-01',  user:'Admin',   note:'Damaged' },
    // { id:8,  date:'2026-03-09', itemId:10,variantKey:'40-Black',type:'Purchase',   qty:+7,  ref:'PO-002',  user:'Admin',   note:'Restock' },
    // { id:9,  date:'2026-03-09', itemId:4, variantKey:'L-Blue',  type:'Sale',       qty:-1,  ref:'INV-222', user:'Cashier', note:'' },
    // { id:10, date:'2026-03-09', itemId:12,variantKey:'Free-Grey',type:'Adjustment',qty:-2,  ref:'ADJ-02',  user:'Admin',   note:'Lost' },
  ],
  _ledgerNextId: 11,

  /* ── Helpers ── */
  getCategoryName(id) { return (this.categories.find(c => c.id === id) || {}).name || '—'; },
  getItem(id)         { return this.items.find(i => i.id === id); },
  variantKey(size, color) { return `${size}-${color}`; },

  getTotalStock() {
    return this.items.reduce((s, i) => s + i.variants.reduce((vs, v) => vs + v.stock, 0), 0);
  },
  getLowStockCount() {
    let n = 0;
    this.items.forEach(i => i.variants.forEach(v => { if (v.stock > 0 && v.stock <= Config.lowStockThresh) n++; }));
    return n;
  },
  getOutOfStockCount() {
    let n = 0;
    this.items.forEach(i => i.variants.forEach(v => { if (v.stock === 0) n++; }));
    return n;
  },
  getStockValue() {
    return this.items.reduce((s, item) =>
      s + item.variants.reduce((vs, v) => vs + v.stock * item.costPrice, 0), 0);
  },
  getStockStatus(qty) {
    if (qty === 0)                          return { label: 'Out of Stock', cls: 'badge-out' };
    if (qty <= Config.lowStockThresh)       return { label: 'Low Stock',    cls: 'badge-low-stock' };
    return                                         { label: 'In Stock',     cls: 'badge-in-stock' };
  },

  /* addLedgerEntry: also adjusts variant stock */
  addLedgerEntry(entry) {
    const item = this.getItem(entry.itemId);
    if (!item) return;
    const variant = item.variants.find(v => `${v.size}-${v.color}` === entry.variantKey);
    if (!variant) return;
    variant.stock += entry.qty;
    if (variant.stock < 0) variant.stock = 0;
    this.ledger.unshift({ id: this._ledgerNextId++, ...entry });
  },

  /* stockAdjust: sets stock to actual value */
  stockAdjust(itemId, variantKey, actualQty, reason) {
    const item = this.getItem(itemId);
    if (!item) return;
    const variant = item.variants.find(v => `${v.size}-${v.color}` === variantKey);
    if (!variant) return;
    const diff = actualQty - variant.stock;
    variant.stock = actualQty;
    this.ledger.unshift({
      id: this._ledgerNextId++,
      date: today(),
      itemId, variantKey,
      type: 'Adjustment',
      qty: diff,
      ref: `ADJ-${String(this._ledgerNextId).padStart(3,'0')}`,
      user: 'Admin',
      note: reason,
    });
  },
  
};


/* ================================================================
   UTILITY FUNCTIONS
   ================================================================ */
function today() { return new Date().toISOString().split('T')[0]; }
function fmt(n)  { return `${Config.currency} ${parseFloat(n).toLocaleString('en-NP', { minimumFractionDigits:2, maximumFractionDigits:2 })}`; }
function esc(s)  { return $('<span>').text(s).html(); }

/* ── Toast Notifications ── */
function toast(msg, type = 'success') {
  const icons = { success:'check-circle-fill', danger:'x-circle-fill', warning:'exclamation-triangle-fill', info:'info-circle-fill' };
  const $t = $(`<div class="toast toast-${type}"><i class="bi bi-${icons[type]}"></i><span>${msg}</span></div>`);
  $('#toastContainer').append($t);
  setTimeout(() => $t.css({ opacity:0, transform:'translateX(20px)', transition: 'all .3s' }), 2800);
  setTimeout(() => $t.remove(), 3100);
}

/* ── Modal helpers ── */
function openModal(id)  { $(`#${id}`).addClass('show'); }
function closeModal(id) { $(`#${id}`).removeClass('show'); }
function closeAllModals() { $('.modal-backdrop').removeClass('show'); }

/* ── Input validation ── */
function validateForm($form) {
  let valid = true;
  $form.find('[required]').each(function () {
    const val = $(this).val().trim();
    const $err = $(this).siblings('.form-error');
    if (!val) {
      $(this).addClass('is-invalid');
      $err.addClass('show').text('This field is required.');
      valid = false;
    } else {
      $(this).removeClass('is-invalid');
      $err.removeClass('show');
    }
  });
  $form.find('[data-type="number"]').each(function () {
    const val = $(this).val();
    if (val !== '' && (isNaN(val) || parseFloat(val) < 0)) {
      $(this).addClass('is-invalid');
      $(this).siblings('.form-error').addClass('show').text('Please enter a valid number.');
      valid = false;
    }
  });
  return valid;
}

/* ── Pagination utility ── */
function paginate(data, page, perPage) {
  const total = data.length;
  const pages = Math.max(1, Math.ceil(total / perPage));
  const start = (page - 1) * perPage;
  const slice = data.slice(start, start + perPage);
  return { data: slice, total, pages, page, perPage, start };
}

function renderPaginationBtns($container, info, onPage) {
  $container.empty();
  const { page, pages } = info;
  const btn = (label, pg, active, disabled) =>
    `<button class="pg-btn ${active?'active':''}" data-pg="${pg}" ${disabled?'disabled':''}>${label}</button>`;

  let html = btn('<i class="bi bi-chevron-left"></i>', page-1, false, page<=1);
  for (let p = 1; p <= pages; p++) {
    if (pages > 7 && (p > 3 && p < pages - 2 && Math.abs(p - page) > 1)) {
      if (p === 4) html += '<span class="pg-btn" style="pointer-events:none;border:none;background:none;color:var(--text-muted);">…</span>';
      continue;
    }
    html += btn(p, p, p === page, false);
  }
  html += btn('<i class="bi bi-chevron-right"></i>', page+1, false, page>=pages);
  $container.html(html);
  $container.find('.pg-btn:not([disabled])').on('click', function () {
    const pg = parseInt($(this).data('pg'));
    if (pg >= 1 && pg <= pages && pg !== page) onPage(pg);
  });
}

/* ── AJAX stub (wire to Laravel routes) ── */
// const API = {
//   get(endpoint, data, cb)     { console.log('[GET]',  endpoint, data); if (cb) cb({}); },
//   post(endpoint, data, cb)    { console.log('[POST]', endpoint, data); if (cb) cb({ success:true }); },
//   put(endpoint, data, cb)     { console.log('[PUT]',  endpoint, data); if (cb) cb({ success:true }); },
//   delete(endpoint, cb)        { console.log('[DEL]',  endpoint);       if (cb) cb({ success:true }); },
//   /*
//   Real implementation:
//   post(endpoint, data, cb) {
//     $.ajax({
//       url: Config.apiBase + endpoint,
//       method: 'POST',
//       data: JSON.stringify(data),
//       contentType: 'application/json',
//       headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//       success: cb,
//       error: (xhr) => toast(xhr.responseJSON?.message || 'Server error', 'danger'),
//     });
//   }
//   */
// };
const API = {

  get(endpoint, cb) {
    $.ajax({
      url: Config.apiBase + endpoint,
      method: 'GET',
      success: cb,
      error: (xhr) => toast(xhr.responseJSON?.message || 'Server error', 'danger'),
    });
  },

  post(endpoint, data, cb) {
    $.ajax({
      url: Config.apiBase + endpoint,
      method: 'POST',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: cb,
      error: (xhr) => toast(xhr.responseJSON?.message || 'Server error', 'danger'),
    });
  },

  put(endpoint, data, cb) {
    $.ajax({
      url: Config.apiBase + endpoint,
      method: 'PUT',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: cb,
      error: (xhr) => toast(xhr.responseJSON?.message || 'Server error', 'danger'),
    });
  },

  delete(endpoint, cb) {
    $.ajax({
      url: Config.apiBase + endpoint,
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: cb,
      error: (xhr) => toast(xhr.responseJSON?.message || 'Server error', 'danger'),
    });
  }
};
/* ── Update Dashboard Stats ── */
function refreshStats() {
  $('#statTotalItems').text(Store.items.length);
  $('#statTotalVariants').text(Store.items.reduce((s, i) => s + i.variants.length, 0));
  $('#statLowStock').text(Store.getLowStockCount());
  $('#statOutOfStock').text(Store.getOutOfStockCount());
  $('#statStockValue').text(fmt(Store.getStockValue()));
  $('#statTotalStock').text(Store.getTotalStock().toLocaleString());
  // Low stock alert banner
  const low = Store.getLowStockCount();
  const out = Store.getOutOfStockCount();
  const $alerts = $('#dashAlerts');
  $alerts.empty();
  if (out > 0) $alerts.append(`<div class="alert-box alert-out"><i class="bi bi-x-circle-fill"></i><span><strong>${out} variant${out>1?'s':''}</strong> are out of stock. Please restock immediately.</span></div>`);
  if (low > 0) $alerts.append(`<div class="alert-box alert-low"><i class="bi bi-exclamation-triangle-fill"></i><span><strong>${low} variant${low>1?'s':''}</strong> are running low (≤${Config.lowStockThresh} units).</span></div>`);
}

/* ── Page Navigation ── */
function showPage(pageId) {
  $('.page').removeClass('active');
  $(`#page-${pageId}`).addClass('active');
  $('.nav-link').removeClass('active');
  $(`.nav-link[data-page="${pageId}"]`).addClass('active');
  // Close sidebar on mobile
  if (window.innerWidth < 992) {
    $('.sidebar').removeClass('open');
    $('.sidebar-backdrop-overlay').removeClass('show');
  }
  // Update topbar title
  const titles = {
    dashboard:  'Dashboard',
    items:      'Item Management',
    categories: 'Category Management',
    stock:      'Current Stock',
    purchase:   'Purchase / Stock In',
    history:    'Stock History / Ledger',
  };
  $('#topbarTitle').text(titles[pageId] || 'Inventory');
}

/* ── Boot ── */
$(document).ready(function () {
  // Store.loadCategories(0);
  ItemMgr.loadItems();
  refreshStats();
  showPage('dashboard');

  // Nav clicks
  $(document).on('click', '.nav-link', function () {
    showPage($(this).data('page'));
  });

  // Sidebar toggle (mobile)
  $('#sidebarToggleBtn').on('click', function () {
    $('.sidebar').toggleClass('open');
    $('.sidebar-backdrop-overlay').toggleClass('show');
  });
  $('.sidebar-backdrop-overlay').on('click', function () {
    $('.sidebar').removeClass('open');
    $(this).removeClass('show');
  });

  // Close modal on backdrop click
  $(document).on('click', '.modal-backdrop', function (e) {
    if ($(e.target).is('.modal-backdrop')) closeAllModals();
  });

  // Close modal on X button
  $(document).on('click', '.modal-close', function () {
    $(this).closest('.modal-backdrop').removeClass('show');
  });
});
