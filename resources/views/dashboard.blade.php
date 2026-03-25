@extends('layout')
@section('content')
<!-- ══════════════════════════════════════════
         PAGE: DASHBOARD
    ══════════════════════════════════════════ -->
    <div class="page" id="page-dashboard">
      <div class="page-head">
        <div class="page-head-left">
          <div class="breadcrumb-bar"><i class="bi bi-house-fill"></i><span class="bc-sep">/</span><span class="bc-cur">Dashboard</span></div>
          <div class="pg-title">Inventory Dashboard</div>
          <div class="pg-sub">Real-time stock overview — Clothing Retail Store</div>
        </div>
        <button class="btn btn-primary" onclick="showPage('purchase'); openModal('purchaseModal');">
          <i class="bi bi-cart-plus-fill"></i> New Purchase
        </button>
      </div>

      <!-- Alerts -->
      <div id="dashAlerts"></div>

      <!-- Stat Grid -->
      <div class="stat-grid">
        <div class="stat-card fade-up d1">
          <div class="stat-icon si-indigo"><i class="bi bi-box-seam"></i></div>
          <div class="stat-body">
            <div class="stat-label">Total Items</div>
            <div class="stat-value" id="statTotalItems">0</div>
            <div class="stat-trend trend-flat"><i class="bi bi-dash"></i> Catalogue</div>
          </div>
        </div>
        <div class="stat-card fade-up d2">
          <div class="stat-icon si-sky"><i class="bi bi-layers"></i></div>
          <div class="stat-body">
            <div class="stat-label">Total Variants</div>
            <div class="stat-value" id="statTotalVariants">0</div>
            <div class="stat-trend trend-flat"><i class="bi bi-dash"></i> Size/Color combos</div>
          </div>
        </div>
        <div class="stat-card fade-up d3">
          <div class="stat-icon si-emerald"><i class="bi bi-boxes"></i></div>
          <div class="stat-body">
            <div class="stat-label">Total Stock Units</div>
            <div class="stat-value" id="statTotalStock">0</div>
            <div class="stat-trend trend-up"><i class="bi bi-arrow-up-short"></i> All variants</div>
          </div>
        </div>
        <div class="stat-card fade-up d4">
          <div class="stat-icon si-amber"><i class="bi bi-exclamation-triangle-fill"></i></div>
          <div class="stat-body">
            <div class="stat-label">Low Stock</div>
            <div class="stat-value" id="statLowStock">0</div>
            <div class="stat-trend trend-down"><i class="bi bi-arrow-down-short"></i> ≤ 10 units</div>
          </div>
        </div>
        <div class="stat-card fade-up">
          <div class="stat-icon si-rose"><i class="bi bi-x-circle-fill"></i></div>
          <div class="stat-body">
            <div class="stat-label">Out of Stock</div>
            <div class="stat-value" id="statOutOfStock">0</div>
            <div class="stat-trend trend-down"><i class="bi bi-arrow-down-short"></i> Need restock</div>
          </div>
        </div>
        <div class="stat-card fade-up">
          <div class="stat-icon si-violet"><i class="bi bi-currency-rupee"></i></div>
          <div class="stat-body">
            <div class="stat-label">Stock Value</div>
            <div class="stat-value" id="statStockValue" style="font-size:1.1rem;">—</div>
            <div class="stat-trend trend-up"><i class="bi bi-arrow-up-short"></i> At cost price</div>
          </div>
        </div>
      </div>

      <!-- Quick Action Cards -->
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:22px;">
        <div class="card" style="cursor:pointer;transition:all var(--dur);" onclick="showPage('items')" onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
          <div class="card-body" style="text-align:center;padding:20px;">
            <div style="width:46px;height:46px;background:var(--accent-soft);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:1.2rem;color:var(--accent);"><i class="bi bi-plus-circle-fill"></i></div>
            <div style="font-weight:700;font-size:.85rem;">Add Item</div>
            <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px;">Create new product</div>
          </div>
        </div>
        <div class="card" style="cursor:pointer;transition:all var(--dur);" onclick="showPage('purchase');PurchaseMgr.init?openModal('purchaseModal'):null" onmouseover="this.style.borderColor='var(--emerald)'" onmouseout="this.style.borderColor='var(--border)'">
          <div class="card-body" style="text-align:center;padding:20px;">
            <div style="width:46px;height:46px;background:var(--emerald-soft);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:1.2rem;color:var(--emerald);"><i class="bi bi-cart-plus-fill"></i></div>
            <div style="font-weight:700;font-size:.85rem;">Stock In</div>
            <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px;">Record purchase</div>
          </div>
        </div>
        <div class="card" style="cursor:pointer;transition:all var(--dur);" onclick="showPage('stock')" onmouseover="this.style.borderColor='var(--sky)'" onmouseout="this.style.borderColor='var(--border)'">
          <div class="card-body" style="text-align:center;padding:20px;">
            <div style="width:46px;height:46px;background:var(--sky-soft);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:1.2rem;color:var(--sky);"><i class="bi bi-clipboard2-data-fill"></i></div>
            <div style="font-weight:700;font-size:.85rem;">View Stock</div>
            <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px;">Current levels</div>
          </div>
        </div>
        <div class="card" style="cursor:pointer;transition:all var(--dur);" onclick="showPage('history')" onmouseover="this.style.borderColor='var(--violet)'" onmouseout="this.style.borderColor='var(--border)'">
          <div class="card-body" style="text-align:center;padding:20px;">
            <div style="width:46px;height:46px;background:var(--violet-soft);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-size:1.2rem;color:var(--violet);"><i class="bi bi-journal-bookmark-fill"></i></div>
            <div style="font-weight:700;font-size:.85rem;">Ledger</div>
            <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px;">Stock history</div>
          </div>
        </div>
      </div>

      <!-- Recent Ledger Preview -->
      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="bi bi-clock-history"></i> Recent Stock Movements</div>
          <button class="btn btn-outline btn-sm" onclick="showPage('history')"><i class="bi bi-arrow-right"></i> View Full Ledger</button>
        </div>
        <div class="card-body-flush">
          <table class="data-table">
            <thead>
              <tr>
                <th>Date</th><th>Item</th><th>Variant</th><th>Type</th><th>Qty Change</th><th>Reference</th>
              </tr>
            </thead>
            <tbody id="dashLedgerBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         PAGE: ITEMS
    ══════════════════════════════════════════ -->
    <div class="page" id="page-items">
      <div class="page-head">
        <div class="page-head-left">
          <div class="breadcrumb-bar"><a onclick="showPage('dashboard')" style="cursor:pointer;">Home</a><span class="bc-sep">/</span><span class="bc-cur">Items</span></div>
          <div class="pg-title">Item Management</div>
          <div class="pg-sub">Manage your clothing catalogue — products, SKUs, variants, and pricing.</div>
        </div>
        <button class="btn btn-primary" id="btnAddItem">
          <i class="bi bi-plus-circle-fill"></i> Add Item
        </button>
      </div>

      <div class="card">
        <div class="table-toolbar">
          <div class="tbar-search">
            <i class="bi bi-search tbar-search-ico"></i>
            <input type="text" id="itemSearchInput" placeholder="Search name, SKU, brand…"/>
          </div>
          <div class="tbar-filter">
            <select id="itemFilterCat" class="form-select">
              <option value="">All Categories</option>
            </select>
          </div>
          <div style="margin-left:auto;display:flex;gap:8px;">
            <button class="btn btn-outline btn-sm" onclick="toast('Importing CSV — wire to Laravel import endpoint','info')">
              <i class="bi bi-upload"></i> Import
            </button>
            <button class="btn btn-outline btn-sm" onclick="toast('Exporting CSV…','info')">
              <i class="bi bi-download"></i> Export
            </button>
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th class="item-sort" data-sort="name">Item <i class="bi bi-chevron-expand"></i></th>
                <th>Category</th>
                <th class="item-sort" data-sort="brand">Brand <i class="bi bi-chevron-expand"></i></th>
                <th class="item-sort" data-sort="costPrice">Cost Price <i class="bi bi-chevron-expand"></i></th>
                <th class="item-sort" data-sort="sellingPrice">Sell Price <i class="bi bi-chevron-expand"></i></th>
                <th style="text-align:center;">Variants</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="itemsTableBody"></tbody>
          </table>
        </div>
        <div class="tbl-pagination">
          <span class="pagination-info" id="itemsPaginationInfo"></span>
          <div class="pagination-btns" id="itemsPaginationBtns"></div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         PAGE: CATEGORIES
    ══════════════════════════════════════════ -->
    <div class="page" id="page-categories">
      <div class="page-head">
        <div class="page-head-left">
          <div class="breadcrumb-bar"><a onclick="showPage('dashboard')" style="cursor:pointer;">Home</a><span class="bc-sep">/</span><span class="bc-cur">Categories</span></div>
          <div class="pg-title">Category Management</div>
          <div class="pg-sub">Organise your clothing inventory into logical categories.</div>
        </div>
        <button class="btn btn-primary" id="btnAddCategory">
          <i class="bi bi-plus-circle-fill"></i> Add Category
        </button>
      </div>

      <div class="card">
        <div class="table-toolbar">
          <div class="tbar-search">
            <i class="bi bi-search tbar-search-ico"></i>
            <input type="text" id="catSearchInput" placeholder="Search categories…"/>
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Category Name</th>
                <th>Description</th>
                <th>Items</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="categoriesTableBody"></tbody>
          </table>
        </div>
        <div class="tbl-pagination">
          <span class="pagination-info" id="catPaginationInfo"></span>
          <div class="pagination-btns" id="catPaginationBtns"></div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         PAGE: CURRENT STOCK
    ══════════════════════════════════════════ -->
    <div class="page" id="page-stock">
      <div class="page-head">
        <div class="page-head-left">
          <div class="breadcrumb-bar"><a onclick="showPage('dashboard')" style="cursor:pointer;">Home</a><span class="bc-sep">/</span><span class="bc-cur">Stock</span></div>
          <div class="pg-title">Current Stock</div>
          <div class="pg-sub">Live inventory levels per item variant — adjust, add or remove stock inline.</div>
        </div>
        <div style="display:flex;gap:8px;">
          <button class="btn btn-success" onclick="toast('Navigate to a row and click + In to add stock','info')">
            <i class="bi bi-plus-circle-fill"></i> Quick Stock In
          </button>
        </div>
      </div>

      <div class="card">
        <div class="table-toolbar">
          <div class="tbar-search">
            <i class="bi bi-search tbar-search-ico"></i>
            <input type="text" id="stockSearchInput" placeholder="Search item name, SKU…"/>
          </div>
          <div class="tbar-filter">
            <select id="stockFilterCat" class="form-select"><option value="">All Categories</option></select>
          </div>
          <div class="tbar-filter">
            <select id="stockFilterStatus" class="form-select">
              <option value="">All Status</option>
              <option value="in_stock">In Stock</option>
              <option value="low_stock">Low Stock</option>
              <option value="out_of_stock">Out of Stock</option>
            </select>
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Variant (Size / Color)</th>
                <th>Category</th>
                <th style="text-align:center;">Stock Qty</th>
                <th>Cost Price</th>
                <th>Sell Price</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="stockTableBody"></tbody>
          </table>
        </div>
        <div class="tbl-pagination">
          <span class="pagination-info" id="stockPaginationInfo"></span>
          <div class="pagination-btns" id="stockPaginationBtns"></div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         PAGE: PURCHASE / STOCK IN
    ══════════════════════════════════════════ -->
    <div class="page" id="page-purchase">
      <div class="page-head">
        <div class="page-head-left">
          <div class="breadcrumb-bar"><a onclick="showPage('dashboard')" style="cursor:pointer;">Home</a><span class="bc-sep">/</span><span class="bc-cur">Purchase</span></div>
          <div class="pg-title">Purchase / Stock In</div>
          <div class="pg-sub">Record supplier purchases — multi-item, auto-updates stock and ledger.</div>
        </div>
        <button class="btn btn-primary" id="btnNewPurchase">
          <i class="bi bi-cart-plus-fill"></i> New Purchase Entry
        </button>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="card-title"><i class="bi bi-clock-history"></i> Recent Purchases</div>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>PO Ref</th>
                <th>Date</th>
                <th>Item</th>
                <th>Variant</th>
                <th>Qty Added</th>
                <th>Notes</th>
                <th>Type</th>
              </tr>
            </thead>
            <tbody id="recentPurchasesBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- <!-- ══════════════════════════════════════════
         PAGE: SALES / POS
    ══════════════════════════════════════════ -->
    <div class="page" id="page-sales">
      <div class="page-head">
        <div class="page-head-left">
          <div class="breadcrumb-bar"><a onclick="showPage('dashboard')" style="cursor:pointer;">Home</a><span class="bc-sep">/</span><span class="bc-cur">Sales / POS</span></div>
          <div class="pg-title">Sales Point (POS)</div>
          <div class="pg-sub">Fast billing system with barcode scanning, IRD-compliant invoicing, and stock integration.</div>
        </div>
      </div>

      <div class="pos-grid">
        <div class="card pos-input-card">
          <div class="card-header">
            <div class="card-title"><i class="bi bi-upc-scan"></i> Scan Or Search Item</div>
          </div>
          <div class="card-body">
            <div class="form-row form-row-2 pos-entry-row">
              <div class="form-group">
                <label class="form-label">Scan Barcode</label>
                <input type="text" id="posBarcodeInput" class="form-control pos-input-large" placeholder="Scan barcode and press Enter" autofocus/>
                <span class="form-hint">Fast lane: scanner or manual barcode entry.</span>
              </div>
              <div class="form-group">
                <label class="form-label">Search Item / SKU</label>
                <div id="posSearchContainer" class="pos-search-wrap">
                  <input type="text" id="posItemSearch" class="form-control" placeholder="Type item name, SKU, color, or size"/>
                  <div id="posSearchResults"></div>
                </div>
                <span class="form-hint">Type at least 2 letters, then tap result to add.</span>
              </div>
            </div>
          </div>
        </div>

        <div class="card pos-summary-card" id="posSummaryCard">
          <div class="card-header">
            <div class="card-title"><i class="bi bi-receipt"></i> Billing Summary</div>
          </div>
          <div class="card-body">
            <div class="pos-stats">
              <div class="pos-stat-box">
                <span>Items</span>
                <strong id="posSummaryItems">0</strong>
              </div>
              <div class="pos-stat-box">
                <span>Qty</span>
                <strong id="posSummaryQty">0</strong>
              </div>
            </div>

            <div class="pos-money-lines">
              <div class="pos-line"><span>Subtotal</span><strong id="posSummarySubtotal">Rs. 0.00</strong></div>
              <div class="pos-line"><span>Discount</span><span id="posSummaryDiscount">Rs. 0.00</span></div>
              <div class="pos-line"><span>Taxable</span><span id="posSummaryTaxable">Rs. 0.00</span></div>
              <div class="pos-line"><span>VAT (13%)</span><span id="posSummaryVat">Rs. 0.00</span></div>
            </div>

            <div class="pos-grand-total-wrap">
              <span>GRAND TOTAL</span>
              <span id="posSummaryGrandTotal">Rs. 0.00</span>
            </div>

            <div class="form-group" style="margin-bottom:8px;">
              <label class="form-label" style="margin-bottom:4px;">Discount %</label>
              <input type="number" id="posDiscountPercent" class="form-control" placeholder="0%" min="0" max="100" step="0.5" value="0"/>
            </div>
            <div class="form-group" style="margin-bottom:0;">
              <label class="form-label" style="margin-bottom:4px;">Payment Method</label>
              <select id="posPaymentMethod" class="form-control form-select">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="fonepay">FonePay</option>
                <option value="esewa">eSewa</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Cart Items -->
      <div class="card pos-cart-card" style="margin-top:16px;">
        <div class="card-header">
          <div class="card-title"><i class="bi bi-cart-fill"></i> Cart Items <span class="pos-cart-count" id="posCartCountBadge">0</span></div>
          <div style="display:flex;gap:8px;">
            <button class="btn btn-outline btn-sm" id="posCartClear">
              <i class="bi bi-trash"></i> Clear Cart
            </button>
            <button class="btn btn-success btn-sm" id="posCheckoutTopBtn">
              <i class="bi bi-check-circle-fill"></i> Checkout
            </button>
          </div>
        </div>
        <div class="pos-cart-table-wrap">
          <table class="data-table" style="margin-bottom:0;">
            <thead>
              <tr>
                <th>Item</th>
                <th>Variant</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
                <th style="width:50px;"></th>
              </tr>
            </thead>
            <tbody id="posCartBody">
              <tr id="posCartEmpty">
                <td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">
                  <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                  Cart is empty. Start scanning barcodes.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Customer & Submission -->
      <div class="pos-footer-grid" style="margin-top:16px;">
        <div class="card">
          <div class="card-body">
            <div class="form-group">
              <label class="form-label">Customer Name (Optional)</label>
              <input type="text" id="posCustomerName" class="form-control" placeholder="e.g. Raj Kumar"/>
            </div>
            <div class="form-group">
              <label class="form-label">Customer PAN (Optional)</label>
              <input type="text" id="posCustomerPan" class="form-control" placeholder="e.g. 5021234567PAN001" maxlength="20"/>
              <span class="form-hint">Format: 9-digit-PAN3-digit</span>
            </div>
          </div>
        </div>

        <div class="card pos-action-card">
          <div class="card-body">
            <button class="btn btn-success" id="posCheckoutBtn" style="width:100%;margin-bottom:8px;padding:16px;font-size:1.1rem;font-weight:700;">
              <i class="bi bi-check-circle-fill"></i> Complete Sale
            </button>
            <button class="btn btn-outline" id="posResetBtn" style="width:100%;">
              <i class="bi bi-arrow-clockwise"></i> New Sale
            </button>
          </div>
        </div>
      </div>

      <div class="pos-mobile-checkout">
        <div class="pos-mobile-total-block">
          <span>Total</span>
          <strong id="posSummaryGrandTotalMini">Rs. 0.00</strong>
        </div>
        <button class="btn btn-success" id="posCheckoutQuickBtn">
          <i class="bi bi-check2-circle"></i> Complete
        </button>
      </div>
    </div> --}}
  <!-- ══════════════════════════════════════════
      PAGE: SALES / POS  — Redesigned UI
      All JS IDs, classes, data-* preserved exactly.
      Layout and visual treatment only improved.
  ══════════════════════════════════════════ -->
  <div class="page" id="page-sales" style="padding: 0; background: var(--bg, #f1f5f9);">

    <!-- ── Top Topbar ─────────────────────────────────────────── -->
    <div style="background:#fff; border-bottom:1px solid #e2e8f0; padding:12px 20px; display:flex; align-items:center; justify-content:space-between; gap:12px; position:sticky; top:0; z-index:100;">
      <div>
        <div style="font-size:.68rem; color:#94a3b8; text-transform:uppercase; letter-spacing:.8px; margin-bottom:2px;">
          <a onclick="showPage('dashboard')" style="cursor:pointer; color:#94a3b8; text-decoration:none;">Home</a>
          <span style="margin:0 5px;">/</span>
          <span style="color:#6366f1;">POS Terminal</span>
        </div>
        <div style="font-size:1.05rem; font-weight:700; color:#0f172a; line-height:1.2;">Sales Point</div>
      </div>
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="font-size:.75rem; color:#94a3b8; display:none;" class="d-sm-inline">
          Cart: <strong id="posCartCountBadge" style="color:#6366f1;">0</strong> items
        </span>
        <button class="btn btn-outline btn-sm" id="posCartClear" style="font-size:.78rem; padding:6px 12px; border-radius:7px;">
          <i class="bi bi-trash"></i> Clear
        </button>
        <button class="btn btn-success btn-sm" id="posCheckoutTopBtn" style="font-size:.78rem; padding:6px 14px; border-radius:7px; background:#10b981; border-color:#10b981;">
          <i class="bi bi-check-circle-fill"></i> Checkout
        </button>
      </div>
    </div>

    <!-- ── Scan Row ───────────────────────────────────────────── -->
    <div style="background:#0f172a; padding:14px 20px;">
      <div style="display:flex; gap:12px; max-width:1400px; margin:0 auto; align-items:flex-end; flex-wrap:wrap;">

        <!-- Barcode -->
        <div style="flex:0 0 340px; min-width:220px;">
          <label style="display:block; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.9px; color:#64748b; margin-bottom:5px;">
            <i class="bi bi-upc-scan" style="color:#6366f1; margin-right:4px;"></i> Barcode
          </label>
          <div style="position:relative;">
            <input type="text" id="posBarcodeInput"
              class="form-control"
              placeholder="Scan or type barcode, press Enter"
              autofocus
              style="background:#1e293b; border:1.5px solid #334155; color:#f1f5f9; border-radius:9px; padding:10px 14px 10px 42px; font-size:.92rem; font-family:monospace; width:100%; outline:none; transition:border-color .15s;"
              onfocus="this.style.borderColor='#6366f1'"
              onblur="this.style.borderColor='#334155'"
            />
            <i class="bi bi-upc" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#475569; font-size:1rem; pointer-events:none;"></i>
          </div>
          <span style="font-size:.65rem; color:#475569; margin-top:4px; display:block;">Press <kbd style="background:#1e293b; border:1px solid #334155; color:#94a3b8; padding:1px 5px; border-radius:3px; font-size:.65rem;">Enter</kbd> to scan</span>
        </div>

        <!-- Item Search -->
        <div style="flex:1; min-width:200px;" id="posSearchContainer" class="pos-search-wrap">
          <label style="display:block; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.9px; color:#64748b; margin-bottom:5px;">
            <i class="bi bi-search" style="color:#6366f1; margin-right:4px;"></i> Search Item
          </label>
          <div style="position:relative;">
            <input type="text" id="posItemSearch"
              class="form-control"
              placeholder="Type item name, SKU, size or color…"
              style="background:#1e293b; border:1.5px solid #334155; color:#f1f5f9; border-radius:9px; padding:10px 14px 10px 42px; font-size:.88rem; width:100%; transition:border-color .15s;"
              onfocus="this.style.borderColor='#6366f1'"
              onblur="this.style.borderColor='#334155'"
            />
            <i class="bi bi-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#475569; pointer-events:none;"></i>
            <!-- Search results dropdown — id preserved -->
            <div id="posSearchResults"
              style="position:absolute; top:calc(100% + 4px); left:0; right:0; background:#1e293b; border:1px solid #334155; border-radius:9px; z-index:999; max-height:280px; overflow-y:auto; box-shadow:0 8px 24px rgba(0,0,0,.4);">
            </div>
          </div>
          <span style="font-size:.65rem; color:#475569; margin-top:4px; display:block;">Type 2+ letters — click result to add</span>
        </div>

        <!-- Live Grand Total Chip -->
        <div style="text-align:right; min-width:130px; padding-bottom:6px;">
          <div style="font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#64748b; margin-bottom:3px;">Grand Total</div>
          <div id="posSummaryGrandTotalMini" style="font-size:1.35rem; font-weight:800; color:#4ade80; font-family:monospace; line-height:1;">Rs. 0.00</div>
        </div>
      </div>
    </div>

    <!-- ── Main Two-Column Layout ─────────────────────────────── -->
    <div style="display:flex; gap:0; max-width:1400px; margin:0 auto; padding:20px; align-items:flex-start; flex-wrap:wrap; gap:16px;">

      <!-- ══════════════════════════════════════
          LEFT — Cart (70%)
      ══════════════════════════════════════ -->
      <div style="flex:1 1 60%; min-width:300px;">

        <!-- Cart Card -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.06);">

          <!-- Cart Header -->
          <div style="padding:14px 18px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; background:#fafafa;">
            <div style="display:flex; align-items:center; gap:8px;">
              <div style="width:32px; height:32px; background:#eef2ff; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                <i class="bi bi-cart-fill" style="color:#6366f1; font-size:.9rem;"></i>
              </div>
              <div>
                <div style="font-size:.88rem; font-weight:700; color:#0f172a;">Cart Items</div>
                <div style="font-size:.68rem; color:#94a3b8;">
                  <span id="posSummaryItems" style="color:#6366f1; font-weight:700;">0</span> products •
                  <span id="posSummaryQty" style="color:#6366f1; font-weight:700;">0</span> units
                </div>
              </div>
            </div>
            <!-- Cart count badge — id preserved -->
            <div style="display:flex; align-items:center; gap:8px;">
              <span style="background:#eef2ff; color:#4f46e5; font-size:.7rem; font-weight:700; padding:3px 10px; border-radius:20px;">
                <span id="posCartCountBadge" style="display:none;"></span><!-- JS still updates this -->
              </span>
            </div>
          </div>

          <!-- Cart Table -->
          <div class="pos-cart-table-wrap" style="overflow-x:auto;">
            <table class="data-table" style="margin-bottom:0; width:100%; border-collapse:collapse;">
              <thead>
                <tr style="background:#f8fafc;">
                  <th style="padding:10px 16px; text-align:left; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#64748b; border-bottom:1px solid #e2e8f0; width:35%;">Item</th>
                  <th style="padding:10px 8px; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#64748b; border-bottom:1px solid #e2e8f0;">Variant</th>
                  <th class="text-center" style="padding:10px 8px; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#64748b; border-bottom:1px solid #e2e8f0; width:80px;">Qty</th>
                  <th class="text-right" style="padding:10px 8px; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#64748b; border-bottom:1px solid #e2e8f0;">Price</th>
                  <th class="text-right" style="padding:10px 8px; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#64748b; border-bottom:1px solid #e2e8f0;">Total</th>
                  <th style="width:44px; border-bottom:1px solid #e2e8f0;"></th>
                </tr>
              </thead>
              <tbody id="posCartBody">
                <!-- Empty state — JS will replace this -->
                <tr id="posCartEmpty">
                  <td colspan="6" style="text-align:center; padding:48px 24px; color:#94a3b8;">
                    <div style="width:48px; height:48px; background:#f1f5f9; border-radius:12px; display:flex; align-items:center; justify-content:center; margin:0 auto 12px;">
                      <i class="bi bi-inbox" style="font-size:1.4rem; color:#cbd5e1;"></i>
                    </div>
                    <div style="font-weight:600; color:#64748b; margin-bottom:4px;">Cart is empty</div>
                    <div style="font-size:.8rem;">Scan a barcode or search for an item to begin</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Customer Info Card -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:18px; margin-top:14px; box-shadow:0 1px 4px rgba(0,0,0,.06);">
          <div style="font-size:.78rem; font-weight:700; color:#0f172a; margin-bottom:12px; display:flex; align-items:center; gap:6px;">
            <i class="bi bi-person-circle" style="color:#6366f1;"></i> Customer Details <span style="font-weight:400; color:#94a3b8;">(optional)</span>
          </div>
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div class="form-group" style="margin:0;">
              <label class="form-label" style="font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#64748b; margin-bottom:5px; display:block;">Customer Name</label>
              <input type="text" id="posCustomerName" class="form-control"
                placeholder="e.g. Raj Kumar"
                style="border-radius:8px; font-size:.85rem; padding:9px 12px; border:1.5px solid #e2e8f0;"/>
            </div>
            <div class="form-group" style="margin:0;">
              <label class="form-label" style="font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#64748b; margin-bottom:5px; display:block;">
                Customer PAN
                <span style="font-weight:400; text-transform:none; color:#94a3b8; letter-spacing:0;">&nbsp;(9-digit-PAN3)</span>
              </label>
              <input type="text" id="posCustomerPan" class="form-control"
                placeholder="e.g. 5021234567PAN001"
                maxlength="20"
                style="border-radius:8px; font-size:.85rem; padding:9px 12px; border:1.5px solid #e2e8f0; font-family:monospace;"/>
            </div>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════
          RIGHT — Billing Summary (30%)
      ══════════════════════════════════════ -->
      <div style="flex:0 0 300px; min-width:260px; position:sticky; top:72px;">

        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.06);">

          <!-- Summary Header -->
          <div style="background:#0f172a; padding:14px 18px; display:flex; align-items:center; gap:8px;">
            <div style="width:30px; height:30px; background:rgba(99,102,241,.2); border-radius:8px; display:flex; align-items:center; justify-content:center;">
              <i class="bi bi-receipt" style="color:#818cf8; font-size:.85rem;"></i>
            </div>
            <div style="font-size:.88rem; font-weight:700; color:#f1f5f9;">Billing Summary</div>
          </div>

          <div style="padding:16px;">

            <!-- Quick stats row -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:14px;">
              <div style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:8px; padding:10px 12px; text-align:center;">
                <div style="font-size:.63rem; text-transform:uppercase; letter-spacing:.7px; color:#94a3b8; margin-bottom:2px;">Products</div>
                <div id="posSummaryItems" style="font-size:1.3rem; font-weight:800; color:#0f172a; font-family:monospace; line-height:1.1;">0</div>
              </div>
              <div style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:8px; padding:10px 12px; text-align:center;">
                <div style="font-size:.63rem; text-transform:uppercase; letter-spacing:.7px; color:#94a3b8; margin-bottom:2px;">Total Units</div>
                <div id="posSummaryQty" style="font-size:1.3rem; font-weight:800; color:#0f172a; font-family:monospace; line-height:1.1;">0</div>
              </div>
            </div>

            <!-- Money breakdown -->
            <div style="background:#f8fafc; border-radius:10px; padding:12px; margin-bottom:12px;">
              <div class="pos-money-lines">
                <div class="pos-line" style="display:flex; justify-content:space-between; align-items:center; padding:5px 0; border-bottom:1px dashed #e2e8f0;">
                  <span style="font-size:.8rem; color:#64748b;">Subtotal</span>
                  <strong id="posSummarySubtotal" style="font-size:.84rem; color:#0f172a; font-family:monospace;">Rs. 0.00</strong>
                </div>
                <div class="pos-line" style="display:flex; justify-content:space-between; align-items:center; padding:5px 0; border-bottom:1px dashed #e2e8f0;">
                  <span style="font-size:.8rem; color:#64748b;">Discount</span>
                  <span id="posSummaryDiscount" style="font-size:.84rem; color:#ef4444; font-family:monospace;">Rs. 0.00</span>
                </div>
                <div class="pos-line" style="display:flex; justify-content:space-between; align-items:center; padding:5px 0; border-bottom:1px dashed #e2e8f0;">
                  <span style="font-size:.8rem; color:#64748b;">Taxable</span>
                  <span id="posSummaryTaxable" style="font-size:.84rem; color:#0f172a; font-family:monospace;">Rs. 0.00</span>
                </div>
                <div class="pos-line" style="display:flex; justify-content:space-between; align-items:center; padding:5px 0;">
                  <span style="font-size:.8rem; color:#64748b;">VAT (13%)</span>
                  <span id="posSummaryVat" style="font-size:.84rem; color:#0f172a; font-family:monospace;">Rs. 0.00</span>
                </div>
              </div>
            </div>

            <!-- Grand Total Highlight -->
            <div class="pos-grand-total-wrap" style="background:linear-gradient(135deg,#0f172a,#1e293b); border-radius:10px; padding:14px 16px; display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
              <span style="font-size:.78rem; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.8px;">Grand Total</span>
              <span id="posSummaryGrandTotal" style="font-size:1.25rem; font-weight:800; color:#4ade80; font-family:monospace;">Rs. 0.00</span>
            </div>

            <!-- Discount % -->
            <div class="form-group" style="margin-bottom:10px;">
              <label class="form-label" style="font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#64748b; margin-bottom:5px; display:flex; justify-content:space-between; align-items:center;">
                <span><i class="bi bi-percent" style="color:#6366f1;"></i> Discount</span>
                <span id="discountDisplay" style="color:#ef4444; font-family:monospace; font-size:.78rem; font-weight:700;">0%</span>
              </label>
              <input type="number" id="posDiscountPercent" class="form-control"
                placeholder="0" min="0" max="100" step="0.5" value="0"
                style="border-radius:8px; font-size:.88rem; padding:9px 12px; border:1.5px solid #e2e8f0; font-family:monospace; text-align:center;"
                oninput="document.getElementById('discountDisplay').textContent=this.value+'%'"
              />
            </div>

            <!-- Payment Method -->
            <div class="form-group" style="margin-bottom:14px;">
              <label class="form-label" style="font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.6px; color:#64748b; margin-bottom:5px; display:block;">
                <i class="bi bi-credit-card" style="color:#6366f1;"></i> Payment
              </label>
              <select id="posPaymentMethod" class="form-control form-select"
                style="border-radius:8px; font-size:.88rem; padding:9px 12px; border:1.5px solid #e2e8f0;">
                <option value="cash">💵 Cash</option>
                <option value="card">💳 Card</option>
                <option value="fonepay">📱 FonePay</option>
                <option value="esewa">🟢 eSewa</option>
              </select>
            </div>

            <!-- Checkout Btn -->
            <button class="btn btn-success" id="posCheckoutBtn"
              style="width:100%; padding:14px; font-size:.98rem; font-weight:700; border-radius:10px; background:#10b981; border-color:#10b981; letter-spacing:.3px; margin-bottom:8px;">
              <i class="bi bi-check-circle-fill"></i>&nbsp; Complete Sale
            </button>

            <button class="btn btn-outline" id="posResetBtn"
              style="width:100%; padding:10px; font-size:.84rem; border-radius:10px; color:#64748b; border-color:#e2e8f0;">
              <i class="bi bi-arrow-clockwise"></i>&nbsp; New Sale
            </button>

          </div>
        </div>

        <!-- IRD Notice -->
        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:10px 14px; margin-top:10px; display:flex; align-items:flex-start; gap:8px;">
          <i class="bi bi-shield-check" style="color:#d97706; font-size:.9rem; margin-top:1px; flex-shrink:0;"></i>
          <div style="font-size:.7rem; color:#92400e; line-height:1.5;">
            <strong>IRD Compliant Billing</strong><br/>
            VAT 13% applied automatically. Bill number auto-generated per fiscal year.
          </div>
        </div>
      </div>
    </div>

    <!-- ── Mobile Sticky Checkout Bar ────────────────────────── -->
    <div class="pos-mobile-checkout"
      style="display:none; position:fixed; bottom:0; left:0; right:0; background:#0f172a; border-top:1px solid #1e293b; padding:10px 16px; z-index:500; align-items:center; justify-content:space-between; gap:12px;">
      <div class="pos-mobile-total-block" style="flex:1;">
        <div style="font-size:.6rem; text-transform:uppercase; letter-spacing:.8px; color:#64748b;">Total</div>
        <strong id="posSummaryGrandTotalMini" style="font-size:1.15rem; color:#4ade80; font-family:monospace;">Rs. 0.00</strong>
      </div>
      <button class="btn btn-success" id="posCheckoutQuickBtn"
        style="padding:11px 22px; font-size:.9rem; font-weight:700; border-radius:10px; background:#10b981; border-color:#10b981; white-space:nowrap;">
        <i class="bi bi-check2-circle"></i>&nbsp; Complete
      </button>
    </div>

    <!-- Mobile: show sticky bar on small screens -->
    <style>
      @media (max-width: 768px) {
        .pos-mobile-checkout { display: flex !important; }
        #page-sales > div:last-of-type { padding-bottom: 80px; }
        .pos-cart-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
      }
      @media (min-width: 769px) {
        .pos-mobile-checkout { display: none !important; }
      }

      /* ── Cart table rows ──────────────────────────── */
      #posCartBody tr:hover td { background: #f8fafc; }
      #posCartBody td {
        padding: 11px 16px;
        font-size: .84rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #0f172a;
      }
      #posCartBody tr:last-child td { border-bottom: none; }

      /* ── Qty input ────────────────────────────────── */
      .pos-item-qty {
        width: 60px !important;
        text-align: center !important;
        padding: 5px 6px !important;
        font-family: monospace !important;
        font-size: .88rem !important;
        border-radius: 7px !important;
        border: 1.5px solid #e2e8f0 !important;
        background: #fff !important;
        transition: border-color .15s !important;
      }
      .pos-item-qty:focus {
        outline: none !important;
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 3px rgba(99,102,241,.12) !important;
      }

      /* ── Remove button ────────────────────────────── */
      .pos-item-remove {
        width: 30px !important;
        height: 30px !important;
        padding: 0 !important;
        border-radius: 7px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: .78rem !important;
        border: 1.5px solid #fecaca !important;
        background: #fff5f5 !important;
        color: #ef4444 !important;
        transition: all .15s !important;
      }
      .pos-item-remove:hover {
        background: #ef4444 !important;
        color: #fff !important;
        border-color: #ef4444 !important;
      }

      /* ── Search results dropdown ──────────────────── */
      #posSearchResults:empty { display: none; }
      .search-result-item {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 10px 14px !important;
        cursor: pointer !important;
        border-bottom: 1px solid rgba(255,255,255,.05) !important;
        transition: background .1s !important;
      }
      .search-result-item:hover { background: rgba(99,102,241,.12) !important; }
      .search-result-item:last-child { border-bottom: none !important; }
      .search-result-name { font-size: .84rem; font-weight: 600; color: #f1f5f9 !important; }
      .search-result-meta { font-size: .72rem; color: #bac3ce; margin-top: 1px; }
      .search-result-price { font-family: monospace; font-size: .84rem; font-weight: 700; color: #4ade80; white-space: nowrap; margin-left: auto; }
      .search-result-stock { font-size: .68rem; font-weight: 700; padding: 3px 7px; border-radius: 5px; white-space: nowrap; color: #4ade80 !important;}

      /* ── POS barcode input focus on page load ─────── */
      /* #page-sales { display: block; } */
    </style>
  </div>

    <!-- ══════════════════════════════════════════
         PAGE: STOCK HISTORY / LEDGER
    ══════════════════════════════════════════ -->
    <div class="page" id="page-history">
      <div class="page-head">
        <div class="page-head-left">
          <div class="breadcrumb-bar"><a onclick="showPage('dashboard')" style="cursor:pointer;">Home</a><span class="bc-sep">/</span><span class="bc-cur">Stock Ledger</span></div>
          <div class="pg-title">Stock History / Ledger</div>
          <div class="pg-sub">Complete audit trail of every stock movement — purchases, sales, adjustments.</div>
        </div>
        <button class="btn btn-outline" id="btnExportHistory">
          <i class="bi bi-download"></i> Export CSV
        </button>
      </div>

      <div class="card">
        <div class="table-toolbar" style="flex-wrap:wrap;gap:8px;">
          <div class="tbar-search">
            <i class="bi bi-search tbar-search-ico"></i>
            <input type="text" id="historySearchInput" placeholder="Search item, reference…"/>
          </div>
          <div class="tbar-filter">
            <select id="historyFilterType" class="form-select">
              <option value="">All Types</option>
              <option value="Purchase">Purchase</option>
              <option value="Sale">Sale</option>
              <option value="Adjustment">Adjustment</option>
              <option value="Return">Return</option>
            </select>
          </div>
          <div style="display:flex;align-items:center;gap:6px;">
            <label style="font-size:.75rem;color:var(--text-muted);white-space:nowrap;">From</label>
            <input type="date" id="historyDateFrom" class="form-control" style="width:150px;font-size:.8rem;"/>
            <label style="font-size:.75rem;color:var(--text-muted);white-space:nowrap;">To</label>
            <input type="date" id="historyDateTo"   class="form-control" style="width:150px;font-size:.8rem;"/>
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Item</th>
                <th>Variant</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Reference</th>
                <th>Notes</th>
                <th>User</th>
              </tr>
            </thead>
            <tbody id="historyTableBody"></tbody>
          </table>
        </div>
        <div class="tbl-pagination">
          <span class="pagination-info" id="historyPaginationInfo"></span>
          <div class="pagination-btns" id="historyPaginationBtns"></div>
        </div>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /app -->


<!-- ═══════════════════════════════════════════════════════════════
     MODALS
═══════════════════════════════════════════════════════════════ -->

<!-- ── Item Add/Edit Modal ── -->
<div class="modal-backdrop" id="itemModal">
  <div class="modal-box modal-lg">
    <div class="modal-head">
      <h5 id="itemModalTitle"><i class="bi bi-plus-circle"></i> Add New Item</h5>
      <button class="modal-close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form id="itemForm" autocomplete="off">

        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Item Name <span class="form-required">*</span></label>
            <input type="text" id="itemName" class="form-control" placeholder="e.g. Classic Oxford Shirt" required/>
            <span class="form-error"></span>
          </div>
          <div class="form-group">
            <label class="form-label">SKU <span class="form-required">*</span></label>
            <input type="text" id="itemSKU" class="form-control" placeholder="e.g. CLT-001" required/>
            <span class="form-error"></span>
          </div>
        </div>

        <div class="form-row form-row-3">
          <div class="form-group">
            <label class="form-label">Category <span class="form-required">*</span></label>
            <select id="itemCategorySelect" class="form-control form-select" required>
              <option value="">Select Category</option>
            </select>
            <span class="form-error"></span>
          </div>
          <div class="form-group">
            <label class="form-label">Brand</label>
            <input type="text" id="itemBrand" class="form-control" placeholder="e.g. Arrow, Levi's"/>
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <input type="text" id="itemDescription" class="form-control" placeholder="Short product note"/>
          </div>
        </div>

        <div class="form-row form-row-2">
          <div class="form-group">
            <label class="form-label">Cost Price (Rs.) <span class="form-required">*</span></label>
            <input type="number" id="itemCostPrice" class="form-control" placeholder="0.00" min="0" step="0.01" required data-type="number"/>
            <span class="form-error"></span>
          </div>
          <div class="form-group">
            <label class="form-label">Selling Price (Rs.) <span class="form-required">*</span></label>
            <input type="number" id="itemSellingPrice" class="form-control" placeholder="0.00" min="0" step="0.01" required data-type="number"/>
            <span class="form-error"></span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Item Image <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
          <div class="img-upload">
            <input type="file" accept="image/*" id="itemImage"/>
            <i class="bi bi-cloud-arrow-up-fill"></i>
            <p>Drop image here or <strong>browse</strong></p>
            <p style="font-size:.68rem;margin-top:2px;">PNG, JPG up to 5MB</p>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" style="display:flex;align-items:center;justify-content:space-between;">
            <span>Variants (Size / Color) <span class="form-required">*</span></span>
            <button type="button" class="add-variant-btn" id="btnAddVariant" style="width:auto;padding:4px 12px;">
              <i class="bi bi-plus"></i> Add Variant
            </button>
          </label>
          <div class="variant-list-head" style="display:grid;grid-template-columns:1fr 1fr auto;gap:4px;padding:4px 0;margin-bottom:4px;">
            <span style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted);padding-left:12px;">Size</span>
            <span style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted);">Color</span>
            <span style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-muted);">Opening Stock</span>
          </div>
          <div class="variant-list" id="itemVariantList"></div>
          <span class="form-hint">Add one row per size/color combination. Opening stock is for new items.</span>
        </div>

      </form>
    </div>
    <div class="modal-foot">
      <button class="btn btn-outline modal-close">Cancel</button>
      <button class="btn btn-primary" id="btnSaveItem"><i class="bi bi-check-circle-fill"></i> Save Item</button>
    </div>
  </div>
</div>

<!-- ── Category Add/Edit Modal ── -->
<div class="modal-backdrop" id="catModal">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <h5 id="catModalTitle"><i class="bi bi-plus-circle"></i> Add Category</h5>
      <button class="modal-close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Category Name <span class="form-required">*</span></label>
        <input type="text" id="catName" class="form-control" placeholder="e.g. Men's Wear"/>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea id="catDesc" class="form-control" rows="3" placeholder="Brief description of this category…"></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-outline modal-close">Cancel</button>
      <button class="btn btn-primary" id="btnSaveCat"><i class="bi bi-check-circle-fill"></i> Save</button>
    </div>
  </div>
</div>

<!-- ── Stock In / Out Modal ── -->
<div class="modal-backdrop" id="stockInOutModal">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <h5 id="stockInOutModalTitle"><i class="bi bi-plus-circle"></i> Stock In</h5>
      <button class="modal-close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="stockInOutType" value="in"/>

      <div style="background:var(--accent-soft);border:1px solid var(--accent);border-radius:var(--radius-md);padding:12px 14px;margin-bottom:16px;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--accent);margin-bottom:3px;">Item / Variant</div>
        <div style="font-weight:700;font-size:.9rem;" id="stockInOutProduct">—</div>
        <div style="font-size:.78rem;color:var(--text-muted);margin-top:4px;">Current stock: <strong id="stockInOutCurrentQty">—</strong> units</div>
      </div>

      <div class="form-group">
        <label class="form-label">Quantity <span class="form-required">*</span></label>
        <input type="number" id="stockQty" class="form-control" placeholder="Enter quantity" min="1" data-type="number"/>
        <span class="form-error"></span>
      </div>
      <div class="form-group">
        <label class="form-label">Reason / Note</label>
        <input type="text" id="stockReason" class="form-control" placeholder="e.g. Supplier delivery, POS sale…"/>
      </div>
      <div class="form-group">
        <label class="form-label">Date</label>
        <input type="date" id="stockDate" class="form-control"/>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-outline modal-close">Cancel</button>
      <button class="btn btn-primary" id="btnSaveStockInOut"><i class="bi bi-check-circle-fill"></i> Confirm</button>
    </div>
  </div>
</div>

<!-- ── Stock Adjustment Modal ── -->
<div class="modal-backdrop" id="adjModal">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <h5><i class="bi bi-sliders"></i> Stock Adjustment</h5>
      <button class="modal-close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <div style="background:var(--amber-soft);border:1px solid var(--amber);border-radius:var(--radius-md);padding:12px 14px;margin-bottom:16px;">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--amber);margin-bottom:3px;">Adjusting</div>
        <div style="font-weight:700;font-size:.9rem;" id="adjProduct">—</div>
        <div style="font-size:.78rem;color:var(--text-muted);margin-top:4px;">System stock: <strong id="adjSystemQty">—</strong> units</div>
      </div>

      <div class="form-group">
        <label class="form-label">Actual Physical Quantity <span class="form-required">*</span></label>
        <input type="number" id="adjActualQty" class="form-control" placeholder="Enter actual count" min="0" data-type="number"/>
        <span class="form-hint">This is what you physically counted. System will auto-calculate the difference.</span>
      </div>
      <div class="form-group">
        <label class="form-label">Adjustment Reason <span class="form-required">*</span></label>
        <select id="adjReason" class="form-control form-select">
          <option value="">Select reason</option>
          <option value="Lost">Lost</option>
          <option value="Damaged">Damaged / Defective</option>
          <option value="Audit">Stock Audit</option>
          <option value="Theft">Theft / Shrinkage</option>
          <option value="Data Entry Error">Data Entry Error</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Date</label>
        <input type="date" id="adjDate" class="form-control"/>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn btn-outline modal-close">Cancel</button>
      <button class="btn btn-warning" id="btnSaveAdj"><i class="bi bi-check-circle-fill"></i> Apply Adjustment</button>
    </div>
  </div>
</div>

<!-- ── Purchase Modal ── -->
<div class="modal-backdrop" id="purchaseModal">
  <div class="modal-box modal-xl">
    <div class="modal-head">
      <h5><i class="bi bi-cart-plus-fill"></i> New Purchase Entry</h5>
      <button class="modal-close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body">
      <form id="purchaseForm" autocomplete="off">
        <div class="form-row form-row-3" style="margin-bottom:16px;">
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Supplier Name <span class="form-required">*</span></label>
            <input type="text" id="purchaseSupplier" class="form-control" placeholder="e.g. Fashion Hub Pvt. Ltd."/>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Purchase Date</label>
            <input type="date" id="purchaseDate" class="form-control"/>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Notes</label>
            <input type="text" id="purchaseNotes" class="form-control" placeholder="Invoice number, notes…"/>
          </div>
        </div>

        <!-- Items table -->
        <div class="form-group">
          <label class="form-label" style="margin-bottom:8px;">Purchase Items <span class="form-required">*</span></label>
          <div class="purchase-items-table">
            <div class="pit-head">
              <span>Product</span>
              <span>Variant</span>
              <span>Qty</span>
              <span>Cost/Unit</span>
              <span>Total</span>
              <span></span>
            </div>
            <div id="purchaseItemsContainer">
              <!-- Rows added dynamically -->
            </div>
          </div>
          <button type="button" class="add-variant-btn" id="btnAddPurchaseRow" style="margin-top:8px;">
            <i class="bi bi-plus"></i> Add Another Item
          </button>
        </div>

        <!-- Grand total -->
        <div style="display:flex;justify-content:flex-end;align-items:center;gap:12px;padding:12px 14px;background:var(--bg);border-radius:var(--radius-md);border:1px solid var(--border);">
          <span style="font-size:.82rem;font-weight:600;color:var(--text-muted);">Grand Total (Cost):</span>
          <span id="purchaseGrandTotal" style="font-size:1.1rem;font-weight:800;color:var(--accent);font-family:var(--font);">Rs. 0.00</span>
        </div>
      </form>
    </div>
    <div class="modal-foot">
      <button class="btn btn-outline modal-close">Cancel</button>
      <button class="btn btn-primary" id="btnSavePurchase">
        <i class="bi bi-check-circle-fill"></i> Save Purchase &amp; Update Stock
      </button>
    </div>
  </div>
</div>
<style>
  .pos-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 16px;
    align-items: start;
  }

  .pos-input-large {
    font-size: 1.05rem;
    padding: 12px;
    font-weight: 600;
  }

  .pos-search-wrap {
    position: relative;
  }

  #posSearchResults {
    position: absolute;
    top: calc(100% + 2px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 0 0 var(--radius-md) var(--radius-md);
    max-height: 320px;
    overflow-y: auto;
    z-index: 12;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.12);
  }

  #posSearchResults .search-result-item {
    padding: 10px 12px;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    transition: background-color 0.15s;
  }

  #posSearchResults .search-result-item:hover {
    background-color: var(--page-bg);
  }

  #posSearchResults .search-result-item:last-child {
    border-bottom: none;
  }

  .search-result-info {
    flex: 1;
    min-width: 0;
  }

  .search-result-name {
    font-weight: 600;
    color: var(--text);
    font-size: 0.92rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .search-result-meta {
    font-size: 0.79rem;
    color: var(--text-muted);
    margin-top: 2px;
  }

  .search-result-price {
    font-weight: 700;
    color: var(--accent);
    margin: 0 8px;
    text-align: right;
    white-space: nowrap;
  }

  .search-result-stock {
    font-size: 0.78rem;
    padding: 4px 8px;
    border-radius: var(--radius-sm);
    white-space: nowrap;
  }

  .pos-summary-card {
    position: sticky;
    top: 16px;
  }

  .pos-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 10px;
  }

  .pos-stat-box {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 8px;
    display: flex;
    flex-direction: column;
    gap: 3px;
  }

  .pos-stat-box span {
    font-size: 0.76rem;
    color: var(--text-muted);
    font-weight: 600;
  }

  .pos-stat-box strong {
    font-size: 1.05rem;
    color: var(--text);
  }

  .pos-money-lines {
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    padding: 8px 0;
    margin-bottom: 10px;
  }

  .pos-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.84rem;
    padding: 4px 0;
  }

  .pos-grand-total-wrap {
    margin: 10px 0 12px;
    padding: 12px;
    border-radius: var(--radius-sm);
    background: linear-gradient(135deg, var(--accent-soft), #fff);
    border: 1px solid var(--accent-soft);
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 8px;
  }

  .pos-grand-total-wrap span:first-child {
    color: var(--accent);
    font-weight: 700;
    letter-spacing: 0.03em;
    font-size: 0.79rem;
  }

  .pos-grand-total-wrap #posSummaryGrandTotal {
    font-size: 1.45rem;
    font-weight: 900;
    color: var(--accent);
    line-height: 1;
  }

  .pos-cart-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 7px;
    font-size: 0.74rem;
    font-weight: 700;
    border-radius: 999px;
    color: #fff;
    background: var(--accent);
    margin-left: 8px;
  }

  .pos-cart-table-wrap {
    overflow-x: auto;
  }

  .pos-footer-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  .pos-mobile-checkout {
    display: none;
  }

  @media (max-width: 1024px) {
    .pos-grid {
      grid-template-columns: 1fr;
    }

    .pos-summary-card {
      position: static;
    }
  }

  @media (max-width: 768px) {
    .pos-entry-row {
      grid-template-columns: 1fr;
    }

    .pos-footer-grid {
      grid-template-columns: 1fr;
    }

    .pos-cart-card .card-header {
      flex-wrap: wrap;
      gap: 8px;
      align-items: flex-start;
    }

    .pos-cart-card th:nth-child(2),
    .pos-cart-card td:nth-child(2),
    .pos-cart-card th:nth-child(4),
    .pos-cart-card td:nth-child(4) {
      display: none;
    }

    .pos-cart-card th,
    .pos-cart-card td {
      font-size: 0.82rem;
      padding: 8px 6px;
    }

    .pos-mobile-checkout {
      position: sticky;
      bottom: 0;
      z-index: 20;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      background: #fff;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.12);
      padding: 10px;
      margin-top: 14px;
    }

    .pos-mobile-total-block {
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .pos-mobile-total-block span {
      font-size: 0.72rem;
      color: var(--text-muted);
      font-weight: 600;
    }

    .pos-mobile-total-block strong {
      font-size: 1.08rem;
      color: var(--accent);
      font-weight: 800;
      white-space: nowrap;
    }

    #posCheckoutQuickBtn {
      min-height: 46px;
      font-weight: 700;
      padding: 10px 16px;
    }

    #posCheckoutBtn,
    #posResetBtn {
      min-height: 46px;
    }
  }
</style>

@endsection