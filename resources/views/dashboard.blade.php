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
              <option value="in">In Stock</option>
              <option value="low">Low Stock</option>
              <option value="out">Out of Stock</option>
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
          <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:4px;padding:4px 0;margin-bottom:4px;">
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
@endsection