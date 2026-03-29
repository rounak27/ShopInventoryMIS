/**
 * STOCKWISE — Purchase (Stock In) Module
 * Multi-item purchase form → updates stock + ledger
 */

'use strict';

const PurchaseMgr = (() => {
   let currentPage = 1;
    let filterSearch = '';
    let filterDateFrom = '';
    let filterDateTo = '';
  const expandedPurchaseRows = new Set();
  let purchaseRows = [];  // { id, itemId, variantKey, qty, costPrice }
  let rowCounter   = 0;
  function loadPurchases(page = 1) {
        currentPage = page;
        const params = new URLSearchParams({
            page,
            per_page: Config.itemsPerPage,
            search: filterSearch || '',
            date_from: filterDateFrom || '',
            date_to: filterDateTo || ''
        });

        API.get(`/purchases?${params.toString()}`, function(res) {
            if (!res.success) {
                console.error("Failed to load purchases", res);
                return;
            }

            Store.purchases = res.data;
            Store.purchasesMeta = res.meta;

            renderRecentPurchases();
        });
    }

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
    console.log("This is row:",$row);
    
    const itemId   = parseInt($row.find('.pr-item').val());
    const $variant = $row.find('.pr-variant');
    $variant.empty();
    $variant.append('<option value="">Variant</option>');
    if (!itemId) return;
    const item = Store.getItem(itemId);
    if (!item) return;
    // console.log();
    
    item.variants.forEach(v => {
      $variant.append(`<option value="${v.id}">${v.size} / ${v.color} (${v.stock} in stock)</option>`);
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
        <div class="pit-cell pit-field-item">
          <span class="pit-mobile-label">Product</span>
          <select class="form-control form-select pr-item" style="font-size:.8rem;"></select>
        </div>
        <div class="pit-cell pit-field-variant">
          <span class="pit-mobile-label">Variant</span>
          <select class="form-control form-select pr-variant" style="font-size:.8rem;"><option value="">Variant</option></select>
        </div>
        <div class="pit-cell pit-field-qty">
          <span class="pit-mobile-label">Qty</span>
          <input type="number" class="form-control pr-qty" placeholder="Qty" min="1" data-type="number" style="font-size:.8rem;"/>
        </div>
        <div class="pit-cell pit-field-cost">
          <span class="pit-mobile-label">Cost/Unit</span>
          <input type="number" class="form-control pr-cost" placeholder="Cost" min="0" data-type="number" style="font-size:.8rem;"/>
        </div>
        <div class="pit-cell pit-field-total">
          <span class="pit-mobile-label">Total</span>
          <span class="pr-total pit-total">—</span>
        </div>
        <div class="pit-cell pit-field-remove">
          <span class="pit-mobile-label">Action</span>
          <button class="btn btn-ghost danger btn-icon pr-remove-btn" data-row="${id}" title="Remove"><i class="bi bi-x"></i></button>
        </div>
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
  async function savePurchase() {
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

    try {
      const res = await API.post('/purchases', { supplier, date, notes, ref, items });
      if (!res?.success) {
        toast(res?.message || 'Purchase failed.', 'danger');
        return;
      }

      closeModal('purchaseModal');
      loadPurchases(currentPage);
      HistoryMgr.load(1); // Refresh ledger history without re-binding listeners
      if (typeof StockMgr !== 'undefined' && typeof StockMgr.loadStock === 'function') {
        StockMgr.loadStock(1);
      }
      if (typeof ItemMgr !== 'undefined' && typeof ItemMgr.loadItems === 'function') {
        ItemMgr.loadItems(1);
      }
      refreshStats();
      toast(res.message || `Purchase ${ref} saved!`, 'success');
    } catch (err) {
      toast(err?.message || 'Server error occurred.', 'danger');
    }
  }

  /* ── Render recent purchases in the purchase page ── */
  // function renderRecentPurchases() {
  //   const purchases = Store.purchases || [];//Store.ledger.filter(e => e.type === 'Purchase').slice(0, 20);
  //   console.log("Rendering recent purchases", purchases);
  //   const $tbody = $('#recentPurchasesBody');
  //   $tbody.empty();

  //   if (!purchases.length) {
  //     $tbody.html(`<tr><td colspan="7"><div class="empty-state"><i class="bi bi-cart-plus"></i><p>No purchases yet.</p></div></td></tr>`);
  //     return;
  //   }
  //   console.log("Purchases:",purchases);
    
  //   purchases.forEach(entry => {
  //     const item = Store.getItem(entry.itemId);
  //     if (!item) return;
  //     const variant = item.variants.find(v => `${v.size}-${v.color}` === entry.variantKey);
  //     $tbody.append(`
  //       <tr>
  //         <td style="font-family:var(--font-mono);font-size:.75rem;color:var(--text-muted);">${entry.ref}</td>
  //         <td style="color:var(--text-muted);font-size:.8rem;">${entry.date}</td>
  //         <td>
  //           <div class="product-cell">
  //             <div class="product-img">${item.emoji}</div>
  //             <div>
  //               <div class="product-name">${esc(item.name)}</div>
  //               <div class="product-sku">${esc(item.sku)}</div>
  //             </div>
  //           </div>
  //         </td>
  //         <td><span class="sku-chip">${esc(entry.variantKey)}</span></td>
  //         <td class="qty-plus" style="font-weight:700;font-family:var(--font-mono);">+${entry.qty}</td>
  //         <td style="font-size:.8rem;">${entry.note || '—'}</td>
  //         <td><span class="badge badge-purchase">Purchase</span></td>
  //       </tr>`);
  //   });
  // }
function renderRecentPurchases() {
    const purchases = Store.purchases || [];
    const $tbody = $('#recentPurchasesBody');
    $tbody.empty();

    if (!purchases.length) {
        $tbody.html(`<tr><td colspan="8"><div class="empty-state"><i class="bi bi-cart-plus"></i><p>No purchases yet.</p></div></td></tr>`);
        return;
    }

    purchases.forEach(po => {
        const items = Array.isArray(po.items) ? po.items : [];
        const isExpanded = expandedPurchaseRows.has(po.id);
        const hasItems = items.length > 0;
        const toggleIcon = isExpanded ? 'bi-dash-lg' : 'bi-plus-lg';
        const linesLabel = `${items.length} line${items.length === 1 ? '' : 's'}`;
        const detailRows = hasItems
          ? items.map((item, idx) => `
              <tr class="purchase-detail-row ${isExpanded ? '' : 'd-none'}" data-detail-for="${po.id}">
                <td></td>
                <td style="font-family:var(--font-mono);font-size:.75rem;color:var(--text-muted);">${idx + 1}</td>
                <td style="color:var(--text-muted);font-size:.8rem;">${esc(po.purchaseDate)}</td>
                <td>
                  <div class="product-cell">
                    <div class="product-img">📦</div>
                    <div>
                      <div class="product-name">${esc(item.itemName || '—')}</div>
                      <div class="product-sku">${esc(item.sku || '—')}</div>
                    </div>
                  </div>
                </td>
                <td><span class="sku-chip">${esc(item.variantKey || '—')}</span></td>
                <td class="qty-plus" style="font-weight:700;font-family:var(--font-mono);">+${Number(item.quantity || 0)}</td>
                <td style="font-family:var(--font-mono);font-size:.78rem;">${fmt(Number(item.totalCost || 0))}</td>
                <td style="font-size:.8rem;color:var(--text-muted);">${esc(po.notes || '—')}</td>
                <td><span class="badge badge-purchase">Line</span></td>
              </tr>
            `).join('')
          : `
            <tr class="purchase-detail-row ${isExpanded ? '' : 'd-none'}" data-detail-for="${po.id}">
              <td></td>
              <td colspan="7" style="font-size:.8rem;color:var(--text-muted);">No line item details found for this purchase.</td>
            </tr>
          `;

        $tbody.append(`
            <tr class="purchase-main-row" data-purchase-id="${po.id}">
                <td>
                  <button
                    type="button"
                    class="btn btn-ghost btn-icon purchase-expand-btn"
                    data-purchase-id="${po.id}"
                    title="${isExpanded ? 'Hide details' : 'Show details'}"
                    ${hasItems ? '' : 'disabled'}
                  >
                    <i class="bi ${hasItems ? toggleIcon : 'bi-dash'}"></i>
                  </button>
                </td>
                <td style="font-family:var(--font-mono);font-size:.75rem;color:var(--text-muted);">${esc(po.poReference || '—')}</td>
                <td style="color:var(--text-muted);font-size:.8rem;">${esc(po.purchaseDate)}</td>
                <td>${esc(po.supplierName || '—')}</td>
                <td><span class="sku-chip">${linesLabel}</span></td>
                <td style="font-family:var(--font-mono);font-size:.78rem;">${fmt(Number(po.totalCost || 0))}</td>
                <td style="font-size:.8rem;">${esc(po.notes || '—')}</td>
                <td><span class="badge badge-purchase">Purchase</span></td>
            </tr>
            ${detailRows}
        `);
    });
}

  function togglePurchaseDetails(purchaseId) {
    if (expandedPurchaseRows.has(purchaseId)) {
      expandedPurchaseRows.delete(purchaseId);
    } else {
      expandedPurchaseRows.add(purchaseId);
    }
    renderRecentPurchases();
  }
  function init() {
    // renderRecentPurchases();
    loadPurchases();
    $(document).on('click', '#btnNewPurchase', openPurchaseModal);
    $(document).on('click', '#btnAddPurchaseRow', addRow);
    $(document).on('click', '#btnSavePurchase', savePurchase);
    $(document).on('click', '.purchase-expand-btn', function () {
      const purchaseId = parseInt($(this).data('purchase-id'), 10);
      if (!Number.isNaN(purchaseId)) {
        togglePurchaseDetails(purchaseId);
      }
    });
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
  async function load(page = 1) {

  currentPage = page;

  const params = new URLSearchParams({
    search: filterSearch || '',
    type: filterType || '',
    date_from: filterDateFrom || '',
    date_to: filterDateTo || '',
    page: currentPage,
    per_page: Config.itemsPerPage
  });

  try {
    API.get(`/ledger?${params.toString()}`, function(res){

        if(!res.success){
          console.error("Failed to load stock",res);
          return;
        }

        Store.ledger = res.data || [];
        Store.ledgerMeta = res.meta || null;

        render();

      });

  } catch (err) {

    console.error(err);
    toast('Ledger API error', 'error');

  }

}
  function render() {

  let data = Store.ledger || [];

  const $tbody = $('#historyTableBody');
  $tbody.empty();

  if (!data.length) {

    $tbody.html(`
      <tr>
        <td colspan="8">
          <div class="empty-state">
            <i class="bi bi-journal-text"></i>
            <p>No history records found.</p>
          </div>
        </td>
      </tr>
    `);

    return;
  }

  data.forEach(entry => {

    const type = (entry.type || '').toLowerCase();

    const typeConfig = {
      purchase:   { cls: 'badge-purchase',   label: 'Purchase' },
      sale:       { cls: 'badge-sale',       label: 'Sale' },
      adjustment: { cls: 'badge-adjustment', label: 'Adjustment' },
      return:     { cls: 'badge-return',     label: 'Return' },
      damage:     { cls: 'badge-adjustment', label: 'Damage' }
    };

    const tc = typeConfig[type] || { cls: 'badge-dark', label: type };

    const qty = entry.qty;

    $tbody.append(`

      <tr>

        <td style="color:var(--text-muted);font-size:.8rem;white-space:nowrap;">
          ${entry.date}
        </td>

        <td>
          <div class="product-cell">
            <div class="product-img">📦</div>
            <div>
              <div class="product-name">${esc(entry.itemName)}</div>
              <div class="product-sku">${esc(entry.sku)}</div>
            </div>
          </div>
        </td>

        <td>
          <span class="sku-chip">${esc(entry.variantKey)}</span>
        </td>

        <td>
          <span class="badge ${tc.cls}">
            ${tc.label}
          </span>
        </td>

        <td class="qty-change ${qty >= 0 ? 'qty-plus' : 'qty-minus'}"
            style="font-family:var(--font-mono);font-weight:700;">
          ${qty >= 0 ? '+' : ''}${qty}
        </td>

        <td style="font-family:var(--font-mono);font-size:.75rem;color:var(--text-muted);">
          ${entry.ref || '—'}
        </td>

        <td style="font-size:.78rem;color:var(--text-muted);
                   max-width:160px;overflow:hidden;
                   text-overflow:ellipsis;white-space:nowrap;"
            title="${esc(entry.note || '')}">
          ${esc(entry.note || '—')}
        </td>

        <td style="font-size:.78rem;color:var(--text-muted);">
          ${entry.user}
        </td>

      </tr>

    `);

  });

  /* Pagination */

  const meta = Store.ledgerMeta;

  if (meta) {
    const page  = parseInt(meta.page ?? meta.currentPage ?? 1, 10) || 1;
    const pages = parseInt(meta.pages ?? meta.lastPage ?? 1, 10) || 1;

    $('#historyPaginationInfo').text(
      `Page ${page} of ${pages} (${meta.total} records)`
    );

    renderPaginationBtns($('#historyPaginationBtns'), { page, pages }, (p) => {
      load(p);
    });

  } else {
    $('#historyPaginationInfo').text('');
    $('#historyPaginationBtns').empty();
  }

}

  function init() {

  load(1);

  // Search
  $('#historySearchInput').on('input', function () {
    filterSearch = $(this).val().trim();
    load(1);
  });

  $('#historyFilterType').on('change', function () {
    filterType = $(this).val();
    load(1);
  });

  $('#historyDateFrom').on('change', function () {
    filterDateFrom = $(this).val();
    load(1);
  });

  $('#historyDateTo').on('change', function () {
    filterDateTo = $(this).val();
    load(1);
  });

}

  return { init, render, load };
})();
