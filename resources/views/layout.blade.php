<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>StockWise — Inventory Management</title>
  <meta name="description" content="Retail Inventory Management System — Clothing Store"/>
  <link rel="manifest" href="{{ asset('manifest.json') }}"/>
  <meta name="theme-color" content="#0d6efd"/>
  <meta name="mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-capable" content="yes"/>
  <link rel="apple-touch-icon" href="{{ asset('stocklogosmall.png') }}"/>
  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
  <!-- Toastr CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"/>
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
      <a class="nav-link" data-page="sales">
        <i class="bi bi-cash-coin"></i> Sales / POS
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
        <button class="topbar-btn" id="pwaInstallBtn" onclick="triggerPwaInstall()" title="Install App" style="display:none;">
          <i class="bi bi-download"></i>
        </button>
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

    <footer class="border-top bg-white" style="padding:14px 18px;margin-top:18px;">
      <div class="text-center" style="font-size:.82rem;color:#6b7280;line-height:1.5;">
        <div><strong>Designed By Rounak Rajbhandari</strong></div>
        <div>&copy; {{ date('Y') }} StockWise&trade;. All Rights Reserved. <span style="font-size:.75rem;">&#174; Copyright Protected</span></div>
      </div>
    </footer>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="logoutConfirmModalLabel">Confirm Logout</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to logout?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmLogoutBtn">Logout</button>
          </div>
        </div>
      </div>
    </div>

<!-- ═══════════════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<!-- App modules -->
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/init.js') }}"></script>
<script src="{{ asset('js/items.js') }}"></script>
<script src="{{ asset('js/stock.js') }}"></script>
<script src="{{ asset('js/purchase-history.js') }}"></script>
<script src="{{ asset('js/sales.js') }}"></script>
<script>
// ─── PWA Service Worker Registration ───
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register("{{ asset('sw.js') }}").then(function(reg) {
      console.log('✅ Service Worker registered:', reg);
    }).catch(function (err) {
      console.error('❌ Service worker registration failed:', err);
    });
  });
}

// ─── PWA Install Prompt Handler ───
let deferredPrompt = null;
const installBtn = document.getElementById('pwaInstallBtn');
const isIos = /iPhone|iPad|iPod/i.test(navigator.userAgent);
const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
const isSecure = window.isSecureContext || ['localhost', '127.0.0.1'].includes(window.location.hostname);

if (installBtn && !isStandalone) {
  installBtn.style.display = 'flex';
}

window.addEventListener('beforeinstallprompt', (e) => {
  console.log('🔔 Install prompt event FIRED - Saving for later use');
  e.preventDefault(); // Prevent the mini-infobar
  deferredPrompt = e;
  // Show install button when prompt is ready
  if (installBtn) {
    installBtn.style.display = 'flex';
    console.log('✅ Install button is now visible');
  }
});

window.addEventListener('appinstalled', () => {
  console.log('✨ App installed successfully!');
  deferredPrompt = null;
  if (installBtn) installBtn.style.display = 'none';
});

function triggerPwaInstall() {
  console.log('📥 triggerPwaInstall called - deferredPrompt:', !!deferredPrompt);
  if (deferredPrompt) {
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then((choiceResult) => {
      if (choiceResult.outcome === 'accepted') {
        console.log('✅ User accepted install prompt');
      } else {
        console.log('❌ User dismissed install prompt');
      }
      deferredPrompt = null;
    });
    return;
  }

  if (isStandalone) {
    alert('App is already installed on this device.');
    return;
  }

  if (!isSecure) {
    alert('Install prompt requires HTTPS (or localhost). Open this site over HTTPS to enable install prompt.');
    return;
  }

  if (isIos) {
    alert('On iPhone/iPad, use Safari Share menu > Add to Home Screen.');
    return;
  }

  alert('Install prompt is not ready yet. Reload once and interact with the page, or use browser menu > Install app.');
}
</script>

<script>
/* ── Init all modules ── */

$(document).ready(function () {
  function initializeDashboardModules() {
    if (initializeDashboardModules.initialized) return;
    initializeDashboardModules.initialized = true;

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
  }

  if (window.AppAuth?.ready) {
    initializeDashboardModules();
    return;
  }

  $(document).one('app:auth-ready', function () {
    initializeDashboardModules();
  });
});
</script>

</body>
</html>
