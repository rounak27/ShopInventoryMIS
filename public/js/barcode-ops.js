/**
 * Barcode stock operations pages.
 * Handles scan-to-deduct and scan-to-stock-in workflows.
 */

const BarcodeOpsMgr = (() => {
  let selectedStockInVariant = null;

  function apiLookupBarcode(barcode) {
    return $.ajax({
      url: `${Config.apiBase}/variants/barcode/${encodeURIComponent(barcode)}`,
      method: 'GET',
      headers: {
        'Authorization': 'Bearer ' + API.getToken(),
        'Accept': 'application/json'
      }
    });
  }

  function renderVariantCard(variant) {
    const imageMarkup = variant.imageUrl
      ? `<img src="${esc(variant.imageUrl)}" alt="${esc(variant.itemName || 'Variant image')}" style="width:76px;height:76px;object-fit:cover;border-radius:14px;border:1px solid rgba(148,163,184,.25);background:#fff;flex:0 0 auto;"/>`
      : `<div style="width:76px;height:76px;border-radius:14px;border:1px dashed rgba(148,163,184,.35);display:flex;align-items:center;justify-content:center;color:#94a3b8;background:#fff;flex:0 0 auto;"><i class="bi bi-image"></i></div>`;

    return `
      <div class="card border-0" style="background:#f8fafc;">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div style="display:flex;gap:12px;align-items:flex-start;flex:1 1 320px;min-width:0;">
              ${imageMarkup}
              <div style="min-width:0;">
                <div style="font-size:.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">Selected Variant</div>
                <div style="font-size:1rem;font-weight:700;color:#0f172a;word-break:break-word;">${esc(variant.itemName || 'Unknown Item')}</div>
                <div style="color:#64748b;font-size:.88rem;">${esc(variant.size || '—')} / ${esc(variant.color || '—')}</div>
                <div style="margin-top:8px;font-size:.8rem;color:#475569;">SKU: <strong>${esc(variant.sku || '—')}</strong></div>
                <div style="font-size:.8rem;color:#475569;">Barcode: <strong style="font-family:monospace;">${esc(variant.barcode || '—')}</strong></div>
              </div>
            </div>
            <div style="text-align:right;min-width:140px;">
              <div style="font-size:.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;">Current Stock</div>
              <div style="font-size:2rem;font-weight:800;line-height:1;color:${variant.stock > 0 ? '#16a34a' : '#dc2626'};">${variant.stock}</div>
              <div style="font-size:.78rem;color:#64748b;">${variant.status || ''}</div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  function renderOutResult(response, scannedBarcode) {
    const variant = response?.variant || {};
    const ledgerEntry = response?.ledgerEntry || {};
    return `
      <div class="card border-0" style="background:#fff7ed;">
        <div class="card-body">
          <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;">
            <div>
              <div style="font-size:.72rem;color:#c2410c;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;">Stock Deducted</div>
              <div style="font-size:1.1rem;font-weight:800;color:#9a3412;">${esc(variant.itemName || 'Unknown Item')}</div>
              <div style="color:#7c2d12;">${esc(variant.size || '—')} / ${esc(variant.color || '—')}</div>
              <div style="margin-top:6px;font-size:.85rem;color:#9a3412;">Barcode: <strong style="font-family:monospace;">${esc(scannedBarcode)}</strong></div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:.72rem;color:#c2410c;text-transform:uppercase;letter-spacing:.08em;">Qty Change</div>
              <div style="font-size:1.9rem;font-weight:800;line-height:1;color:#dc2626;">-1</div>
              <div style="font-size:.82rem;color:#7c2d12;">Before ${ledgerEntry.stockBefore ?? variant.stock + 1} → After ${ledgerEntry.stockAfter ?? variant.stock}</div>
            </div>
          </div>
          <hr style="margin:14px 0;border-color:#fed7aa;"/>
          <div class="row g-2" style="font-size:.9rem;color:#7c2d12;">
            <div class="col-md-4"><strong>Reference:</strong> ${esc(ledgerEntry.ref || '—')}</div>
            <div class="col-md-4"><strong>Reason:</strong> ${esc(ledgerEntry.note || 'Barcode scan stock out')}</div>
            <div class="col-md-4"><strong>Date:</strong> ${esc(ledgerEntry.date || today())}</div>
          </div>
        </div>
      </div>
    `;
  }

  function renderInStatus(message, type = 'success') {
    const color = type === 'danger' ? '#b91c1c' : (type === 'warning' ? '#b45309' : '#166534');
    const bg = type === 'danger' ? '#fef2f2' : (type === 'warning' ? '#fffbeb' : '#f0fdf4');
    $('#barcodeInStatus').html(`
      <div class="alert" style="background:${bg};color:${color};border:1px solid rgba(0,0,0,.06);margin-bottom:0;">
        ${esc(message)}
      </div>
    `);
  }

  function renderInVariantCard(variant) {
    $('#barcodeInVariantCard').html(renderVariantCard(variant));
  }

  function clearInState() {
    selectedStockInVariant = null;
    $('#barcodeInVariantCard').html(`
      <div class="empty-state">
        <i class="bi bi-box-arrow-in-down"></i>
        <p>Scan a barcode to load the item details.</p>
      </div>
    `);
    $('#barcodeInQty').val(1);
    $('#barcodeInReason').val('');
    $('#barcodeInDate').val(today());
    $('#barcodeInStatus').empty();
  }

  async function handleStockOutScan(barcode) {
    if (!barcode) {
      toast('Scan a barcode first.', 'warning');
      return;
    }

    try {
      const lookup = await apiLookupBarcode(barcode);
      const variant = lookup?.data;

      if (!variant) {
        toast('Barcode not found.', 'warning');
        return;
      }

      const result = await API.post('/stock/adjust', {
        variantId: variant.id,
        operation: 'out',
        quantity: 1,
        reason: 'Barcode scan stock out',
        date: today(),
        note: `Auto deducted from barcode scan: ${barcode}`,
      });

      if (!result?.success) {
        toast(result?.message || 'Failed to deduct stock.', 'danger');
        return;
      }

      const response = result.data || {};
      if (response.ledgerEntry && typeof Store !== 'undefined' && typeof Store.addLedgerEntry === 'function') {
        Store.addLedgerEntry(response.ledgerEntry);
      }

      if (typeof refreshStats === 'function') {
        refreshStats();
      }
      if (typeof StockMgr !== 'undefined' && typeof StockMgr.loadStock === 'function') {
        StockMgr.loadStock(1);
      }
      $(document).trigger('ledger:updated', [response.ledgerEntry]);

      $('#barcodeOutResult').html(renderOutResult(response, barcode));
      $('#barcodeOutInput').val('').focus();
      toast(result.message || 'Stock reduced by 1.', 'success');
    } catch (error) {
      const message = error?.responseJSON?.message || error?.message || 'Barcode lookup failed.';
      toast(message, 'danger');
    }
  }

  async function handleStockInScan(barcode) {
    if (!barcode) {
      toast('Scan a barcode first.', 'warning');
      return;
    }

    try {
      const lookup = await apiLookupBarcode(barcode);
      const variant = lookup?.data;

      if (!variant) {
        toast('Barcode not found.', 'warning');
        return;
      }

      selectedStockInVariant = variant;
      renderInVariantCard(variant);
      renderInStatus(`Loaded ${variant.itemName || 'item'} — enter qty, reason, and date, then save.`, 'success');
      $('#barcodeInQty').focus().select();
    } catch (error) {
      selectedStockInVariant = null;
      const message = error?.responseJSON?.message || error?.message || 'Barcode lookup failed.';
      $('#barcodeInStatus').empty();
      toast(message, 'danger');
    }
  }

  async function submitStockIn() {
    if (!selectedStockInVariant) {
      toast('Scan a barcode first.', 'warning');
      return;
    }

    const qty = parseInt($('#barcodeInQty').val(), 10);
    const reason = $('#barcodeInReason').val().trim();
    const date = $('#barcodeInDate').val() || today();

    if (!qty || qty < 1) {
      toast('Enter a valid quantity.', 'warning');
      return;
    }

    try {
      const result = await API.post('/stock/adjust', {
        variantId: selectedStockInVariant.id,
        operation: 'in',
        quantity: qty,
        reason: reason || 'Barcode stock in',
        date: date,
        note: reason,
      });

      if (!result?.success) {
        toast(result?.message || 'Failed to add stock.', 'danger');
        return;
      }

      const response = result.data || {};
      if (response.ledgerEntry && typeof Store !== 'undefined' && typeof Store.addLedgerEntry === 'function') {
        Store.addLedgerEntry(response.ledgerEntry);
      }

      if (typeof refreshStats === 'function') {
        refreshStats();
      }
      if (typeof StockMgr !== 'undefined' && typeof StockMgr.loadStock === 'function') {
        StockMgr.loadStock(1);
      }
      $(document).trigger('ledger:updated', [response.ledgerEntry]);

      renderInStatus(result.message || 'Stock added successfully.', 'success');
      $('#barcodeInQty').val(1);
      $('#barcodeInReason').val('');
      $('#barcodeInDate').val(today());
      $('#barcodeInInput').val('').focus();
      toast(result.message || 'Stock increased successfully.', 'success');
    } catch (error) {
      const message = error?.responseJSON?.message || error?.message || 'Stock in failed.';
      renderInStatus(message, 'danger');
      toast(message, 'danger');
    }
  }

  function bindEvents() {
    $('#barcodeOutInput').on('keypress', function (event) {
      if (event.which === 13) {
        event.preventDefault();
        const barcode = $(this).val().trim();
        handleStockOutScan(barcode);
      }
    });

    $('#barcodeInInput').on('keypress', function (event) {
      if (event.which === 13) {
        event.preventDefault();
        const barcode = $(this).val().trim();
        handleStockInScan(barcode);
      }
    });

    $('#barcodeOutClearBtn').on('click', function () {
      $('#barcodeOutInput').val('').focus();
      $('#barcodeOutResult').html(`
        <div class="empty-state">
          <i class="bi bi-upc-scan"></i>
          <p>Scan a barcode to deduct one unit.</p>
        </div>
      `);
    });

    $('#btnBarcodeInSubmit').on('click', submitStockIn);

    $(document).on('page:changed', function (_event, pageId) {
      if (pageId === 'barcode-out') {
        $('#barcodeOutInput').focus().select();
      }
      if (pageId === 'barcode-in') {
        $('#barcodeInInput').focus().select();
        if (!$('#barcodeInDate').val()) {
          $('#barcodeInDate').val(today());
        }
      }
    });
  }

  function init() {
    if (init.initialized) return;
    init.initialized = true;
    bindEvents();
    clearInState();
    $('#barcodeInDate').val(today());
  }

  return {
    init,
  };
})();
