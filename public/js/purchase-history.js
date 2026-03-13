/**
 * STOCKWISE — Purchase (Stock In) Module
 * Multi-item purchase form → updates stock + ledger
 */

'use strict';

const PurchaseMgr = (() => {

  let purchaseRows = [];  // { id, itemId, variantKey, qty, costPrice }
  let rowCounter   = 0;

  /* ── Populate item selects ── */
  function populateItemSelect($select, selectedItemId) {
    const cur = selectedItemId || $select.val();
    $select.empty();
    $select.append('<option value="">Select Item</option>');
    Store.items.forEach(i => {
      $select.append(`<option value="${i.id}" ${parseInt(cur)===i.id?'selected':''}>${esc(i.name)} (${i.sku})</option>`);
    });
  }

  /* ── Update variant select when item changes ── */
  function updateVariantSelect($row) {
    const itemId   = parseInt($row.find('.pr-item').val());
    const $variant = $row.find('.pr-variant');
    $variant.empty();
    $variant.append('<option value="">Variant</option>');
    if (!itemId) return;
    const item = Store.getItem(itemId);
    if (!item) return;
    item.variants.forEach(v => {
      $variant.append(`<option value="${v.size}-${v.color}">${v.size} / ${v.color} (${v.stock} in stock)</option>`);
    });
    // Pre-fill cost price
    $row.find('.pr-cost').val(item.costPrice);
    updateRowTotal($row);
  }

  function updateRowTotal($row) {
    const qty  = parseFloat($row.find('.pr-qty').val())  || 0;
    const cost = parseFloat($row.find('.pr-cost').val()) || 0;
    $row.find('.pr-total').text(fmt(qty * cost));
  }

  /* ── Add purchase row ── */
  function addRow() {
    rowCounter++;
    const id = rowCounter;
    const $row = $(`
      <div class="pit-row" data-row="${id}">
        <select class="form-control form-select pr-item" style="font-size:.8rem;"></select>
        <select class="form-control form-select pr-variant" style="font-size:.8rem;"><option value="">Variant</option></select>
        <input type="number" class="form-control pr-qty" placeholder="Qty" min="1" data-type="number" style="font-size:.8rem;"/>
        <input type="number" class="form-control pr-cost" placeholder="Cost" min="0" data-type="number" style="font-size:.8rem;"/>
        <span class="pr-total pit-total">—</span>
        <button class="btn btn-ghost danger btn-icon pr-remove-btn" data-row="${id}" title="Remove"><i class="bi bi-x"></i></button>
      </div>`);
    populateItemSelect($row.find('.pr-item'));
    $('#purchaseItemsContainer').append($row);

    // Events for this row
    $row.find('.pr-item').on('change', function () { updateVariantSelect($(this).closest('.pit-row')); });
    $row.find('.pr-qty, .pr-cost').on('input', function () { updateRowTotal($(this).closest('.pit-row')); updateGrandTotal(); });
    $row.find('.pr-remove-btn').on('click', function () {
      if ($('#purchaseItemsContainer .pit-row').length <= 1) { toast('At least one item row required.', 'warning'); return; }
      $(this).closest('.pit-row').remove();
      updateGrandTotal();
    });
  }

  function updateGrandTotal() {
    let total = 0;
    $('#purchaseItemsContainer .pit-row').each(function () {
      const qty  = parseFloat($(this).find('.pr-qty').val())  || 0;
      const cost = parseFloat($(this).find('.pr-cost').val()) || 0;
      total += qty * cost;
    });
    $('#purchaseGrandTotal').text(fmt(total));
  }

  /* ── Open modal ── */
  function openPurchaseModal() {
    $('#purchaseForm')[0].reset();
    $('#purchaseDate').val(today());
    $('#purchaseItemsContainer').empty();
    addRow();
    updateGrandTotal();
    openModal('purchaseModal');
  }

  /* ── Save purchase ── */
  function savePurchase() {
    const supplier = $('#purchaseSupplier').val().trim();
    const date     = $('#purchaseDate').val() || today();
    const notes    = $('#purchaseNotes').val().trim();

    if (!supplier) { toast('Supplier name is required.', 'warning'); return; }

    const items = [];
    let valid = true;

    $('#purchaseItemsContainer .pit-row').each(function () {
      const itemId     = parseInt($(this).find('.pr-item').val());
      const variantKey = $(this).find('.pr-variant').val();
      const qty        = parseInt($(this).find('.pr-qty').val());
      const costPrice  = parseFloat($(this).find('.pr-cost').val());

      if (!itemId || !variantKey || !qty || qty <= 0) { valid = false; return; }
      items.push({ itemId, variantKey, qty, costPrice });
    });

    if (!valid || !items.length) { toast('Please fill all item rows correctly.', 'warning'); return; }

    const ref = `PO-${Date.now().toString().slice(-6)}`;

    // Process each item
    items.forEach(row => {
      const entry = {
        date,
        itemId:      row.itemId,
        variantKey:  row.variantKey,
        type:        'Purchase',
        qty:         +row.qty,
        ref,
        user:        'Admin',
        note:        `Supplier: ${supplier}${notes ? ' | ' + notes : ''}`,
      };
      Store.addLedgerEntry(entry);
    });

    // POST to Laravel API
    API.post('/purchases', { supplier, date, notes, ref, items });

    toast(`Purchase ${ref} saved! Stock updated for ${items.length} variant(s).`, 'success');
    closeModal('purchaseModal');

    // Refresh all tables
    StockMgr.render();
    HistoryMgr.render();
    refreshStats();
    renderRecentPurchases();
  }

  /* ── Render recent purchases in the purchase page ── */
  function renderRecentPurchases() {
    const purchases = Store.ledger.filter(e => e.type === 'Purchase').slice(0, 20);
    const $tbody = $('#recentPurchasesBody');
    $tbody.empty();

    if (!purchases.length) {
      $tbody.html(`<tr><td colspan="7"><div class="empty-state"><i class="bi bi-cart-plus"></i><p>No purchases yet.</p></div></td></tr>`);
      return;
    }

    purchases.forEach(entry => {
      const item = Store.getItem(entry.itemId);
      if (!item) return;
      const variant = item.variants.find(v => `${v.size}-${v.color}` === entry.variantKey);
      $tbody.append(`
        <tr>
          <td style="font-family:var(--font-mono);font-size:.75rem;color:var(--text-muted);">${entry.ref}</td>
          <td style="color:var(--text-muted);font-size:.8rem;">${entry.date}</td>
          <td>
            <div class="product-cell">
              <div class="product-img">${item.emoji}</div>
              <div>
                <div class="product-name">${esc(item.name)}</div>
                <div class="product-sku">${esc(item.sku)}</div>
              </div>
            </div>
          </td>
          <td><span class="sku-chip">${esc(entry.variantKey)}</span></td>
          <td class="qty-plus" style="font-weight:700;font-family:var(--font-mono);">+${entry.qty}</td>
          <td style="font-size:.8rem;">${entry.note || '—'}</td>
          <td><span class="badge badge-purchase">Purchase</span></td>
        </tr>`);
    });
  }

  function init() {
    renderRecentPurchases();
    $(document).on('click', '#btnNewPurchase', openPurchaseModal);
    $(document).on('click', '#btnAddPurchaseRow', addRow);
    $(document).on('click', '#btnSavePurchase', savePurchase);
  }

  return { init, renderRecentPurchases };
})();


/* ================================================================
   STOCK HISTORY / LEDGER MODULE
   ================================================================ */

const HistoryMgr = (() => {

  let currentPage    = 1;
  let filterSearch   = '';
  let filterType     = '';
  let filterDateFrom = '';
  let filterDateTo   = '';

  const typeConfig = {
    Purchase:   { cls: 'badge-purchase',   label: 'Purchase'   },
    Sale:       { cls: 'badge-sale',       label: 'Sale'       },
    Adjustment: { cls: 'badge-adjustment', label: 'Adjustment' },
    Return:     { cls: 'badge-return',     label: 'Return'     },
  };

  function render() {
    let data = Store.ledger.map(entry => {
      const item = Store.getItem(entry.itemId) || {};
      return { ...entry, itemName: item.name || '—', sku: item.sku || '—', emoji: item.emoji || '📦' };
    });

    if (filterSearch) {
      const q = filterSearch.toLowerCase();
      data = data.filter(e => e.itemName.toLowerCase().includes(q) || (e.ref || '').toLowerCase().includes(q) || (e.note || '').toLowerCase().includes(q));
    }
    if (filterType)     data = data.filter(e => e.type === filterType);
    if (filterDateFrom) data = data.filter(e => e.date >= filterDateFrom);
    if (filterDateTo)   data = data.filter(e => e.date <= filterDateTo);

    // Sort by date desc (already in reverse insert order but ensure)
    data.sort((a, b) => b.date.localeCompare(a.date) || b.id - a.id);

    const pg     = paginate(data, currentPage, Config.itemsPerPage);
    const $tbody = $('#historyTableBody');
    $tbody.empty();

    if (!pg.data.length) {
      $tbody.html(`<tr><td colspan="8"><div class="empty-state"><i class="bi bi-journal-text"></i><p>No history records found.</p></div></td></tr>`);
    } else {
      pg.data.forEach(entry => {
        const tc  = typeConfig[entry.type] || { cls: 'badge-dark', label: entry.type };
        const qty = entry.qty;
        $tbody.append(`
          <tr>
            <td style="color:var(--text-muted);font-size:.8rem;white-space:nowrap;">${entry.date}</td>
            <td>
              <div class="product-cell">
                <div class="product-img">${entry.emoji}</div>
                <div>
                  <div class="product-name">${esc(entry.itemName)}</div>
                  <div class="product-sku">${esc(entry.sku)}</div>
                </div>
              </div>
            </td>
            <td><span class="sku-chip">${esc(entry.variantKey)}</span></td>
            <td><span class="badge ${tc.cls}">${tc.label}</span></td>
            <td class="qty-change ${qty >= 0 ? 'qty-plus' : 'qty-minus'}" style="font-family:var(--font-mono);font-weight:700;">
              ${qty >= 0 ? '+' : ''}${qty}
            </td>
            <td style="font-family:var(--font-mono);font-size:.75rem;color:var(--text-muted);">${entry.ref || '—'}</td>
            <td style="font-size:.78rem;color:var(--text-muted);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${esc(entry.note || '')}">${esc(entry.note || '—')}</td>
            <td style="font-size:.78rem;color:var(--text-muted);">${entry.user}</td>
          </tr>`);
      });
    }

    $('#historyPaginationInfo').text(`Showing ${pg.start+1}–${Math.min(pg.start+pg.perPage, pg.total)} of ${pg.total} records`);
    renderPaginationBtns($('#historyPaginationBtns'), pg, (p) => { currentPage = p; render(); });
  }

  function init() {
    render();

    // Search
    $('#historySearchInput').on('input', function () {
      filterSearch = $(this).val().trim();
      currentPage  = 1;
      render();
    });

    // Type filter
    $('#historyFilterType').on('change', function () {
      filterType  = $(this).val();
      currentPage = 1;
      render();
    });

    // Date filters
    $('#historyDateFrom').on('change', function () { filterDateFrom = $(this).val(); currentPage = 1; render(); });
    $('#historyDateTo').on('change',   function () { filterDateTo   = $(this).val(); currentPage = 1; render(); });

    // Export CSV stub
    $(document).on('click', '#btnExportHistory', function () {
      toast('Exporting ledger to CSV…', 'info');
    });
  }

  return { init, render };
})();
