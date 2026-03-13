<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>StockWise — Inventory Management</title>
  <meta name="description" content="Retail Inventory Management System — Clothing Store"/>
  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
  <!-- Custom Styles -->
  <link rel="stylesheet" href="{{ asset('css/style.css') }}"/>
</head>
<body>
<div id="baseUrl" class="card d-none" > {{ url('/') }}</div>

<!-- ═══════════════════════════════════════════════════
     TOAST CONTAINER
════════════════════════════════════════════════════ -->
<div class="toast-container" id="toastContainer"></div>

<!-- ═══════════════════════════════════════════════════
     SIDEBAR BACKDROP (mobile)
════════════════════════════════════════════════════ -->
<div class="sidebar-backdrop-overlay" id="sidebarBackdropOverlay"></div>

<!-- ═══════════════════════════════════════════════════
     APP SHELL
════════════════════════════════════════════════════ -->
<div class="app">

  <!-- ── Sidebar ── -->
  <aside class="sidebar" id="appSidebar">
    <div class="sidebar-logo">
      <div class="logo-mark">SW</div>
      <div>
        <div class="logo-name">StockWise</div>
        <div class="logo-sub">Inventory Module</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-group-label">Overview</div>
      <a class="nav-link" data-page="dashboard">
        <i class="bi bi-grid-1x2-fill"></i> Dashboard
      </a>

      <div class="nav-group-label">Catalogue</div>
      <a class="nav-link" data-page="items">
        <i class="bi bi-box-seam-fill"></i> Item Management
      </a>
      <a class="nav-link" data-page="categories">
        <i class="bi bi-tag-fill"></i> Categories
      </a>

      <div class="nav-group-label">Stock Control</div>
      <a class="nav-link" data-page="stock">
        <i class="bi bi-clipboard2-data-fill"></i> Current Stock
        <span class="nav-count" id="navLowCount" style="display:none;">0</span>
      </a>
      <a class="nav-link" data-page="purchase">
        <i class="bi bi-cart-plus-fill"></i> Purchase / Stock In
      </a>

      <div class="nav-group-label">Reports</div>
      <a class="nav-link" data-page="history">
        <i class="bi bi-journal-bookmark-fill"></i> Stock Ledger
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="user-pill">
        <div class="user-avatar">AD</div>
        <div>
          <div class="user-name">Admin User</div>
          <div class="user-role">Inventory Manager</div>
        </div>
        <i class="bi bi-box-arrow-right user-logout" title="Logout"></i>
      </div>
    </div>
  </aside>

  <!-- ── Main Content ── -->
  <div class="content">

    <!-- Topbar -->
    <header class="topbar">
      <button class="sidebar-toggle-btn" id="sidebarToggleBtn"><i class="bi bi-list"></i></button>
      <span class="topbar-title" id="topbarTitle">Dashboard</span>
      <div class="topbar-search">
        <i class="bi bi-search search-ico"></i>
        <input type="text" placeholder="Quick search items, SKU…" id="globalSearch"/>
      </div>
      <div class="topbar-right">
        <button class="topbar-btn" title="Notifications">
          <i class="bi bi-bell"></i>
          <span class="badge-dot"></span>
        </button>
        <button class="topbar-btn" title="Export" onclick="toast('Export feature — wire to Laravel PDF/Excel export','info')">
          <i class="bi bi-download"></i>
        </button>
        <button class="topbar-btn" title="Settings">
          <i class="bi bi-gear"></i>
        </button>
      </div>
    </header>

    @yield('content')

<!-- ═══════════════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- App modules -->
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/items.js') }}"></script>
<script src="{{ asset('js/stock.js') }}"></script>
<script src="{{ asset('js/purchase-history.js') }}"></script>

<script>
/* ── Init all modules ── */
$(document).ready(function () {

  ItemMgr.init();
  CatMgr.init();
  StockMgr.init();
  PurchaseMgr.init();
  HistoryMgr.init();

  /* ── Dashboard ledger preview ── */
  function renderDashLedger() {
    const recent = Store.ledger.slice(0, 8);
    const typeConfig = {
      Purchase:   'badge-purchase',
      Sale:       'badge-sale',
      Adjustment: 'badge-adjustment',
    };
    const $tbody = $('#dashLedgerBody');
    $tbody.empty();
    recent.forEach(e => {
      const item = Store.getItem(e.itemId) || {};
      const cls  = typeConfig[e.type] || 'badge-dark';
      const plus = e.qty >= 0;
      $tbody.append(`
        <tr>
          <td style="color:var(--text-muted);font-size:.78rem;">${e.date}</td>
          <td>
            <div style="display:flex;align-items:center;gap:7px;">
              <span style="font-size:1.1rem;">${item.emoji || '📦'}</span>
              <div>
                <div style="font-weight:600;font-size:.82rem;">${esc(item.name || '—')}</div>
                <div style="font-size:.7rem;color:var(--text-muted);font-family:var(--font-mono);">${esc(item.sku || '')}</div>
              </div>
            </div>
          </td>
          <td><span class="sku-chip">${esc(e.variantKey)}</span></td>
          <td><span class="badge ${cls}">${e.type}</span></td>
          <td class="${plus ? 'qty-plus' : 'qty-minus'}" style="font-family:var(--font-mono);font-weight:700;">${plus?'+':''}${e.qty}</td>
          <td style="font-family:var(--font-mono);font-size:.73rem;color:var(--text-muted);">${e.ref || '—'}</td>
        </tr>`);
    });
  }
  renderDashLedger();

  /* ── Update low stock count badge in sidebar ── */
  function updateSidebarBadge() {
    const lowCount = Store.getLowStockCount() + Store.getOutOfStockCount();
    const $badge = $('#navLowCount');
    if (lowCount > 0) { $badge.text(lowCount).show(); }
    else { $badge.hide(); }
  }
  updateSidebarBadge();

  /* ── Global search → go to items page ── */
  $('#globalSearch').on('input', function () {
    const q = $(this).val().trim();
    if (q) {
      showPage('items');
      $('#itemSearchInput').val(q).trigger('input');
    }
  });

  /* ── Keyboard shortcut: Ctrl+K → focus search ── */
  $(document).on('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      $('#globalSearch').focus();
    }
  });

  console.log('%c📦 StockWise Inventory Module loaded', 'color:#6366f1;font-weight:700;font-size:13px;');
});
</script>

</body>
</html>
