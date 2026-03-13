/**
 * STOCKWISE — Category Management Module
 */

'use strict';

const CatMgr = (() => {

  let currentPage  = 1;
  let filterSearch = '';
  let editingId    = null;

  function render() {
    let data = Store.categories.map(c => ({
      ...c,
      itemCount: Store.items.filter(i => i.categoryId === c.id).length,
    }));

    if (filterSearch) {
      const q = filterSearch.toLowerCase();
      data = data.filter(c => c.name.toLowerCase().includes(q) || (c.description || '').toLowerCase().includes(q));
    }

    const pg = paginate(data, currentPage, Config.itemsPerPage);
    const $tbody = $('#categoriesTableBody');
    $tbody.empty();

    if (!pg.data.length) {
      $tbody.html(`<tr><td colspan="5"><div class="empty-state"><i class="bi bi-tag"></i><p>No categories found.</p></div></td></tr>`);
    } else {
      pg.data.forEach((cat, idx) => {
        $tbody.append(`
          <tr data-id="${cat.id}">
            <td style="color:var(--text-muted);font-family:var(--font-mono);font-size:.78rem;">${pg.start + idx + 1}</td>
            <td style="font-weight:600;">${esc(cat.name)}</td>
            <td style="color:var(--text-muted);font-size:.8rem;">${esc(cat.description || '—')}</td>
            <td><span class="sku-chip">${cat.itemCount} item${cat.itemCount !== 1 ? 's' : ''}</span></td>
            <td style="color:var(--text-muted);font-size:.78rem;">${cat.createdAt}</td>
            <td>
              <div style="display:flex;gap:4px;">
                <button class="btn btn-ghost info btn-icon cat-edit-btn" data-id="${cat.id}" title="Edit"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-ghost danger btn-icon cat-del-btn" data-id="${cat.id}" title="Delete"><i class="bi bi-trash"></i></button>
              </div>
            </td>
          </tr>`);
      });
    }

    $('#catPaginationInfo').text(`Showing ${pg.start+1}–${Math.min(pg.start+pg.perPage, pg.total)} of ${pg.total} categories`);
    renderPaginationBtns($('#catPaginationBtns'), pg, (p) => { currentPage = p; render(); });
  }

  function openAdd() {
    editingId = null;
    $('#catModalTitle').html('<i class="bi bi-plus-circle"></i> Add Category');
    $('#catName').val('');
    $('#catDesc').val('');
    openModal('catModal');
  }

  function openEdit(id) {
    editingId = id;
    const cat = Store.categories.find(c => c.id === id);
    if (!cat) return;
    $('#catModalTitle').html('<i class="bi bi-pencil"></i> Edit Category');
    $('#catName').val(cat.name);
    $('#catDesc').val(cat.description || '');
    openModal('catModal');
  }

  function saveCat() {
    const name = $('#catName').val().trim();
    if (!name) { toast('Category name is required.', 'warning'); return; }

    const payload = { name, description: $('#catDesc').val().trim() };

    if (editingId) {
      API.put(`/categories/${editingId}`, payload);
      const cat = Store.categories.find(c => c.id === editingId);
      Object.assign(cat, payload);
      toast('Category updated!', 'success');
    } else {
      API.post('/categories', payload);
      Store.categories.push({ id: Store._catNextId++, createdAt: today(), itemCount: 0, ...payload });
      toast('Category added!', 'success');
    }

    closeModal('catModal');
    render();
    ItemMgr.populateCatDropdowns();
    StockMgr.populateFilters();
  }

  function deleteCat(id) {
    const itemCount = Store.items.filter(i => i.categoryId === id).length;
    if (itemCount > 0) { toast(`Cannot delete — ${itemCount} item(s) use this category.`, 'warning'); return; }
    if (!confirm('Delete this category?')) return;
    API.delete(`/categories/${id}`);
    Store.categories = Store.categories.filter(c => c.id !== id);
    toast('Category deleted.', 'danger');
    render();
    ItemMgr.populateCatDropdowns();
    StockMgr.populateFilters();
  }

  function init() {
    render();
    $(document).on('click', '#btnAddCategory', openAdd);
    $(document).on('click', '.cat-edit-btn',   function () { openEdit(parseInt($(this).data('id'))); });
    $(document).on('click', '.cat-del-btn',    function () { deleteCat(parseInt($(this).data('id'))); });
    $(document).on('click', '#btnSaveCat',     saveCat);
    $('#catSearchInput').on('input', function () {
      filterSearch = $(this).val().trim();
      currentPage  = 1;
      render();
    });
  }

  return { init, render };
})();


/* ================================================================
   STOCK MANAGEMENT MODULE
   Current Stock View + Stock In / Out / Adjust
   ================================================================ */

const StockMgr = (() => {

  let currentPage  = 1;
  let filterSearch = '';
  let filterCat    = '';
  let filterStatus = '';
  // For stock in/out/adjust modals
  let activeItemId    = null;
  let activeVariantKey = null;

  /* ── Populate filter dropdowns ── */
  function populateFilters() {
    const $cat = $('#stockFilterCat, #purchaseItemFilter');
    $cat.each(function () {
      const cur = $(this).val();
      const isFilter = $(this).attr('id') === 'stockFilterCat';
      $(this).empty();
      $(this).append(isFilter ? '<option value="">All Categories</option>' : '<option value="">Filter by Category</option>');
      Store.categories.forEach(c => {
        $(this).append(`<option value="${c.id}" ${parseInt(cur)===c.id?'selected':''}>${esc(c.name)}</option>`);
      });
    });
  }

  /* ── Build flat stock rows ── */
  function buildStockRows() {
    const rows = [];
    Store.items.forEach(item => {
      item.variants.forEach(v => {
        rows.push({
          itemId:      item.id,
          itemName:    item.name,
          sku:         item.sku,
          categoryId:  item.categoryId,
          categoryName:Store.getCategoryName(item.categoryId),
          brand:       item.brand,
          emoji:       item.emoji,
          size:        v.size,
          color:       v.color,
          variantKey:  `${v.size}-${v.color}`,
          stock:       v.stock,
          costPrice:   item.costPrice,
          sellingPrice:item.sellingPrice,
        });
      });
    });
    return rows;
  }

  /* ── Render stock table ── */
  function render() {
    let data = buildStockRows();

    if (filterSearch) {
      const q = filterSearch.toLowerCase();
      data = data.filter(r => r.itemName.toLowerCase().includes(q) || r.sku.toLowerCase().includes(q));
    }
    if (filterCat)    data = data.filter(r => r.categoryId === parseInt(filterCat));
    if (filterStatus === 'in')   data = data.filter(r => r.stock > Config.lowStockThresh);
    if (filterStatus === 'low')  data = data.filter(r => r.stock > 0 && r.stock <= Config.lowStockThresh);
    if (filterStatus === 'out')  data = data.filter(r => r.stock === 0);

    const pg = paginate(data, currentPage, Config.itemsPerPage);
    const $tbody = $('#stockTableBody');
    $tbody.empty();

    if (!pg.data.length) {
      $tbody.html(`<tr><td colspan="9"><div class="empty-state"><i class="bi bi-clipboard2-data"></i><p>No stock records found.</p></div></td></tr>`);
    } else {
      pg.data.forEach(row => {
        const st = Store.getStockStatus(row.stock);
        $tbody.append(`
          <tr>
            <td>
              <div class="product-cell">
                <div class="product-img">${row.emoji}</div>
                <div>
                  <div class="product-name">${esc(row.itemName)}</div>
                  <div class="product-sku">${esc(row.sku)}</div>
                </div>
              </div>
            </td>
            <td>
              <span class="sku-chip">${esc(row.size)}</span>
              <span style="margin:0 3px;color:var(--text-xlight);">|</span>
              <span style="font-size:.78rem;color:var(--text-muted);">${esc(row.color)}</span>
            </td>
            <td><span class="badge" style="background:var(--accent-soft);color:var(--accent);font-size:.65rem;">${esc(row.categoryName)}</span></td>
            <td style="font-family:var(--font-mono);font-weight:700;font-size:.9rem;text-align:center;">${row.stock}</td>
            <td>${fmt(row.costPrice)}</td>
            <td>${fmt(row.sellingPrice)}</td>
            <td><span class="badge ${st.cls}">${st.label}</span></td>
            <td>
              <div style="display:flex;gap:4px;flex-wrap:wrap;">
                <button class="btn btn-sm btn-success stock-in-btn" data-item="${row.itemId}" data-vk="${row.variantKey}" title="Add Stock">
                  <i class="bi bi-plus-circle"></i> In
                </button>
                <button class="btn btn-sm btn-danger stock-out-btn" data-item="${row.itemId}" data-vk="${row.variantKey}" title="Remove Stock">
                  <i class="bi bi-dash-circle"></i> Out
                </button>
                <button class="btn btn-sm btn-outline stock-adj-btn" data-item="${row.itemId}" data-vk="${row.variantKey}" title="Adjust Stock">
                  <i class="bi bi-sliders"></i>
                </button>
              </div>
            </td>
          </tr>`);
      });
    }

    $('#stockPaginationInfo').text(`Showing ${pg.start+1}–${Math.min(pg.start+pg.perPage, pg.total)} of ${pg.total} variants`);
    renderPaginationBtns($('#stockPaginationBtns'), pg, (p) => { currentPage = p; render(); });
  }

  /* ── Filter by item (called from Items page) ── */
  function filterByItem(itemId) {
    const item = Store.getItem(itemId);
    if (!item) return;
    filterSearch = item.name;
    $('#stockSearchInput').val(item.name);
    currentPage = 1;
    render();
  }

  /* ── Open Stock In/Out modal ── */
  function openStockInOut(type, itemId, variantKey) {
    activeItemId     = itemId;
    activeVariantKey = variantKey;
    const item    = Store.getItem(itemId);
    if (!item) return;
    const variant = item.variants.find(v => `${v.size}-${v.color}` === variantKey);
    if (!variant) return;

    const isIn = type === 'in';
    $('#stockInOutModalTitle').html(
      isIn ? '<i class="bi bi-plus-circle"></i> Add Stock (Stock In)'
           : '<i class="bi bi-dash-circle"></i> Remove Stock (Stock Out)'
    );
    $('#stockInOutProduct').text(`${item.name} — ${variant.size} / ${variant.color}`);
    $('#stockInOutCurrentQty').text(variant.stock);
    $('#stockInOutType').val(type);
    $('#stockQty').val('');
    $('#stockReason').val('');
    $('#stockDate').val(today());
    openModal('stockInOutModal');
  }

  /* ── Save Stock In/Out ── */
  function saveStockInOut() {
    const qty    = parseInt($('#stockQty').val());
    const reason = $('#stockReason').val().trim();
    const date   = $('#stockDate').val() || today();
    const type   = $('#stockInOutType').val();

    if (!qty || qty <= 0) { toast('Enter a valid quantity (> 0).', 'warning'); return; }

    const delta  = type === 'in' ? qty : -qty;
    const action = type === 'in' ? 'Purchase' : 'Sale';

    // Validate out stock
    if (type === 'out') {
      const item    = Store.getItem(activeItemId);
      const variant = item.variants.find(v => `${v.size}-${v.color}` === activeVariantKey);
      if (variant.stock < qty) { toast(`Only ${variant.stock} units in stock. Cannot remove ${qty}.`, 'warning'); return; }
    }

    const entry = { date, itemId: activeItemId, variantKey: activeVariantKey, type: action, qty: delta, ref: `${action.toUpperCase().slice(0,3)}-${Date.now().toString().slice(-5)}`, user: 'Admin', note: reason };
    API.post('/stock/move', entry);
    Store.addLedgerEntry(entry);

    toast(`Stock ${type === 'in' ? 'added' : 'removed'} successfully!`, 'success');
    closeModal('stockInOutModal');
    render();
    HistoryMgr.render();
    refreshStats();
  }

  /* ── Open Adjust modal ── */
  function openAdjust(itemId, variantKey) {
    activeItemId     = itemId;
    activeVariantKey = variantKey;
    const item    = Store.getItem(itemId);
    if (!item) return;
    const variant = item.variants.find(v => `${v.size}-${v.color}` === variantKey);
    if (!variant) return;

    $('#adjProduct').text(`${item.name} — ${variant.size} / ${variant.color}`);
    $('#adjSystemQty').text(variant.stock);
    $('#adjActualQty').val(variant.stock);
    $('#adjReason').val('');
    $('#adjDate').val(today());
    openModal('adjModal');
  }

  /* ── Save Adjustment ── */
  function saveAdjustment() {
    const actual = parseInt($('#adjActualQty').val());
    const reason = $('#adjReason').val().trim();
    const date   = $('#adjDate').val() || today();

    if (isNaN(actual) || actual < 0) { toast('Enter a valid actual quantity.', 'warning'); return; }

    const payload = { itemId: activeItemId, variantKey: activeVariantKey, actualQty: actual, reason, date };
    API.post('/stock/adjust', payload);

    Store.stockAdjust(activeItemId, activeVariantKey, actual, reason);
    toast('Stock adjusted successfully!', 'success');
    closeModal('adjModal');
    render();
    HistoryMgr.render();
    refreshStats();
  }

  function init() {
    populateFilters();
    render();

    // Stock In button
    $(document).on('click', '.stock-in-btn', function () {
      openStockInOut('in', parseInt($(this).data('item')), $(this).data('vk'));
    });

    // Stock Out button
    $(document).on('click', '.stock-out-btn', function () {
      openStockInOut('out', parseInt($(this).data('item')), $(this).data('vk'));
    });

    // Adjust button
    $(document).on('click', '.stock-adj-btn', function () {
      openAdjust(parseInt($(this).data('item')), $(this).data('vk'));
    });

    // Save stock in/out
    $(document).on('click', '#btnSaveStockInOut', saveStockInOut);

    // Save adjustment
    $(document).on('click', '#btnSaveAdj', saveAdjustment);

    // Search
    $('#stockSearchInput').on('input', function () {
      filterSearch = $(this).val().trim();
      currentPage  = 1;
      render();
    });

    // Category filter
    $('#stockFilterCat').on('change', function () {
      filterCat   = $(this).val();
      currentPage = 1;
      render();
    });

    // Status filter
    $('#stockFilterStatus').on('change', function () {
      filterStatus = $(this).val();
      currentPage  = 1;
      render();
    });
  }

  return { init, render, populateFilters, filterByItem };
})();
