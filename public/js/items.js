/**
 * STOCKWISE — Item Management Module
 * CRUD for items + variants
 */

'use strict';

const ItemMgr = (() => {

  let currentPage   = 1;
  let filterSearch  = '';
  let filterCat     = '';
  let sortCol       = 'name';
  let sortDir       = 'asc';
  let editingId     = null;
  const expandedRows = new Set();
  
  /* ── Render item table ── */
  // function render() {
  //   // Build dataset
  //   let data = Store.items.map(item => ({
  //     ...item,
  //     categoryName: Store.getCategoryName(item.categoryId),
  //     totalStock:   item.variants.reduce((s, v) => s + v.stock, 0),
  //     variantCount: item.variants.length,
  //   }));

  //   // Filter
  //   if (filterSearch) {
  //     const q = filterSearch.toLowerCase();
  //     data = data.filter(i =>
  //       i.name.toLowerCase().includes(q) ||
  //       i.sku.toLowerCase().includes(q) ||
  //       i.brand.toLowerCase().includes(q)
  //     );
  //   }
  //   if (filterCat) data = data.filter(i => i.categoryId === parseInt(filterCat));

  //   // Sort
  //   data.sort((a, b) => {
  //     let va = a[sortCol], vb = b[sortCol];
  //     if (typeof va === 'string') { va = va.toLowerCase(); vb = vb.toLowerCase(); }
  //     if (va < vb) return sortDir === 'asc' ? -1 : 1;
  //     if (va > vb) return sortDir === 'asc' ?  1 : -1;
  //     return 0;
  //   });

  //   const pg   = paginate(data, currentPage, Config.itemsPerPage);
  //   const $tbody = $('#itemsTableBody');
  //   $tbody.empty();

  //   if (!pg.data.length) {
  //     $tbody.html(`<tr><td colspan="8"><div class="empty-state"><i class="bi bi-box-seam"></i><p>No items found.</p></div></td></tr>`);
  //   } else {
  //     pg.data.forEach(item => {
  //       const st = Store.getStockStatus(item.totalStock);
  //       $tbody.append(`
  //         <tr data-id="${item.id}">
  //           <td>
  //             <div class="product-cell">
  //               <div class="product-img">${item.emoji}</div>
  //               <div>
  //                 <div class="product-name">${esc(item.name)}</div>
  //                 <div class="product-sku">${esc(item.sku)}</div>
  //               </div>
  //             </div>
  //           </td>
  //           <td><span class="badge" style="background:var(--accent-soft);color:var(--accent);font-size:.68rem;">${esc(item.categoryName)}</span></td>
  //           <td>${esc(item.brand)}</td>
  //           <td>${fmt(item.costPrice)}</td>
  //           <td>${fmt(item.sellingPrice)}</td>
  //           <td class="text-center"><span class="sku-chip">${item.variantCount}</span></td>
  //           <td><span class="badge ${st.cls}">${st.label}</span></td>
  //           <td>
  //             <div style="display:flex;gap:4px;">
  //               <button class="btn btn-ghost info btn-icon item-edit-btn" data-id="${item.id}" title="Edit"><i class="bi bi-pencil"></i></button>
  //               <button class="btn btn-ghost success btn-icon item-stock-btn" data-id="${item.id}" title="Stock Operations"><i class="bi bi-boxes"></i></button>
  //               <button class="btn btn-ghost danger btn-icon item-del-btn" data-id="${item.id}" title="Delete"><i class="bi bi-trash"></i></button>
  //             </div>
  //           </td>
  //         </tr>`);
  //     });
  //   }

  //   // Pagination
  //   $('#itemsPaginationInfo').text(`Showing ${pg.start+1}–${Math.min(pg.start+pg.perPage, pg.total)} of ${pg.total} items`);
  //   renderPaginationBtns($('#itemsPaginationBtns'), pg, (p) => { currentPage = p; render(); });
  // }
  function loadCategories(){
    console.log('Loading categories from API...');
    API.get('/categories', function(res){
      console.log('API');
      console.log('Response:', res);
      Store.categories = res.data.map(c => ({
        id: c.id,
        name: c.name,
        description: c.description,
        createdAt: c.created_at,
        itemCount: c.items_count
      }));
      populateCatDropdowns();
      console.log('Loaded categories:', Store.categories);
      CatMgr.render(); // your existing table renderer
    });
  }
  function loadItems(page = 1) {

      API.get(`/items?page=${page}&per_page=${Config.itemsPerPage}&search=${filterSearch || ''}&category_id=${filterCat || ''}`,
      function(res){

        currentPage = page;
        Store.items = res.data;
        Store.itemsMeta = res.meta;

        const visibleIds = new Set((res.data || []).map(i => i.id));
        expandedRows.forEach(id => {
          if (!visibleIds.has(id)) expandedRows.delete(id);
        });

        ItemMgr.render();
    });
  }

  function getVariantLabel(variant) {
    if (!variant) return 'Variant';
    return `${variant.size || ''}${variant.color ? ` / ${variant.color}` : ''}`.trim() || 'Variant';
  }
  //building barcoded svg using JsBarcode library
  function buildBarcodeSvg(barcodeValue) {
    if (typeof JsBarcode === 'undefined') return '';

    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    JsBarcode(svg, barcodeValue, {
      format: 'CODE128',
      displayValue: false,
      width: 1.35,
      height: 28,
      margin: 0,
      background: '#ffffff'
    });
    return svg.outerHTML;
  }

  function printBarcodeLabels(labels) {
    if (!labels.length) {
      toast('No barcode labels to print.', 'warning');
      return;
    }

    const printWin = window.open('', '_blank', 'width=420,height=320');
    if (!printWin) {
      toast('Please allow popups to print barcode.', 'warning');
      return;
    }

    const labelHtml = labels.map((label, idx) => `
      <div class="label ${idx < labels.length - 1 ? 'label-break' : ''}">
        <div class="item-name">${esc(label.itemName || '')}</div>
        <div class="variant-name">${esc(label.variantName || '')}</div>
        <div class="barcode">${label.barcodeSvg || `<span class="barcode-fallback">${esc(label.barcodeValue)}</span>`}</div>
        <div class="barcode-code">${esc(label.barcodeValue)}</div>
      </div>
    `).join('');

    printWin.document.write(`
      <!doctype html>
      <html>
      <head>
        <meta charset="utf-8" />
        <title>Barcode Labels</title>
        <style>
          @page {
            size: 2in 1in;
            margin: 0;
          }
          html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            font-family: Arial, sans-serif;
          }
          .label {
            width: 2in;
            height: 1in;
            box-sizing: border-box;
            padding: 0.05in 0.06in;
            display: grid;
            grid-template-rows: auto auto 1fr auto;
            align-content: stretch;
            border: 1px solid #111;
          }
          .label-break { page-break-after: always; }
          .item-name {
            font-size: 10px;
            line-height: 1.1;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
          .variant-name {
            font-size: 8px;
            line-height: 1.1;
            color: #111;
            margin-top: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
          .barcode {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 0;
            overflow: hidden;
          }
          .barcode svg {
            width: 100%;
            height: 100%;
            max-height: 0.44in;
          }
          .barcode-fallback {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.2px;
          }
          .barcode-code {
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 9px;
            line-height: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: 0.3px;
          }
        </style>
      </head>
      <body>
        ${labelHtml}
      </body>
      </html>
    `);

    printWin.document.close();
    printWin.focus();
    setTimeout(() => {
      printWin.print();
      printWin.close();
    }, 250);
  }

  function printVariantBarcode(itemId, variantId) {
    const item = Store.items.find(i => i.id === itemId);
    if (!item) {
      toast('Item not found for barcode print.', 'warning');
      return;
    }

    const variant = (item.variants || []).find(v => v.id === variantId);
    if (!variant) {
      toast('Variant not found for barcode print.', 'warning');
      return;
    }

    const barcodeValue = String(variant.barcode || '').trim();
    if (!barcodeValue) {
      toast('This variant has no barcode value.', 'warning');
      return;
    }

    printBarcodeLabels([{
      itemName: item.name || '',
      variantName: getVariantLabel(variant),
      barcodeValue,
      barcodeSvg: buildBarcodeSvg(barcodeValue)
    }]);
  }

  function renderVariantDetailsRow(item) {
    const variants = Array.isArray(item.variants) ? item.variants : [];
    const rows = variants.length
      ? variants.map(v => {
          const barcodeValue = String(v.barcode || '').trim();
          return `
            <tr>
              <td>${esc(v.size || '-')}</td>
              <td>${esc(v.color || '-')}</td>
              <td class="text-center"><span class="sku-chip">${v.stock ?? 0}</span></td>
              <td><span class="sku-chip">${barcodeValue ? esc(barcodeValue) : 'No barcode'}</span></td>
              <td class="variant-actions-cell">
                <button class="btn btn-ghost btn-icon item-variant-barcode-btn" data-id="${item.id}" data-variant-id="${v.id}" title="Print this barcode" ${barcodeValue ? '' : 'disabled'}>
                  <i class="bi bi-printer"></i>
                </button>
              </td>
            </tr>
          `;
        }).join('')
      : `<tr><td colspan="5" class="variant-empty-msg">No variants available.</td></tr>`;

    return `
      <tr class="item-variant-details-row" data-parent-id="${item.id}">
        <td colspan="9">
          <div class="variant-panel">
            <div class="variant-panel-head">
              <span>Variants for ${esc(item.name || 'Item')}</span>
              <button class="btn btn-outline btn-xs item-barcode-btn" data-id="${item.id}" title="Print all variant barcodes">
                <i class="bi bi-upc-scan"></i> Print All
              </button>
            </div>
            <div class="variant-panel-body">
              <table class="variant-inline-table">
                <thead>
                  <tr>
                    <th>Size</th>
                    <th>Color</th>
                    <th style="text-align:center;">Stock</th>
                    <th>Barcode</th>
                    <th>Print</th>
                  </tr>
                </thead>
                <tbody>${rows}</tbody>
              </table>
            </div>
          </div>
        </td>
      </tr>
    `;
  }

  function render() {

  const data = Array.isArray(Store.items) ? [...Store.items] : [];
  const meta = Store.itemsMeta;

  data.sort((a, b) => {
    let va = a[sortCol];
    let vb = b[sortCol];

    if (typeof va === 'string') va = va.toLowerCase();
    if (typeof vb === 'string') vb = vb.toLowerCase();

    if (va < vb) return sortDir === 'asc' ? -1 : 1;
    if (va > vb) return sortDir === 'asc' ? 1 : -1;
    return 0;
  });

  const $tbody = $('#itemsTableBody');
  $tbody.empty();

  if (!data.length) {
    $tbody.html(`
      <tr>
        <td colspan="9">
          <div class="empty-state">
            <i class="bi bi-box-seam"></i>
            <p>No items found.</p>
          </div>
        </td>
      </tr>
    `);
    return;
  }
  console.log("Data from API:",data);
  
  data.forEach(item => {
    const variants = Array.isArray(item.variants) ? item.variants : [];
    const totalStock   = variants.reduce((s, v) => s + (v.stock || 0), 0);
    const variantCount = variants.length;
    const isExpanded = expandedRows.has(item.id);

    const st = Store.getStockStatus(totalStock);

    $tbody.append(`
      <tr data-id="${item.id}">
        <td class="item-expand-cell">
          <button class="btn btn-ghost btn-icon item-expand-btn" data-id="${item.id}" title="${isExpanded ? 'Hide variants' : 'Show variants'}" ${variantCount ? '' : 'disabled'}>
            <i class="bi ${isExpanded ? 'bi-dash-lg' : 'bi-plus-lg'}"></i>
          </button>
        </td>
        <td>
          <div class="product-cell">
            <div class="product-img">${item.emoji || '📦'}</div>
            <div>
              <div class="product-name">${esc(item.name)}</div>
              <div class="product-sku">${esc(item.sku)}</div>
            </div>
          </div>
        </td>

        <td>
          <span class="badge" style="background:var(--accent-soft);color:var(--accent);font-size:.68rem;">
            ${esc(item.category ?? '')}
          </span>
        </td>

        <td>${esc(item.brand ?? '')}</td>

        <td>${fmt(item.costPrice)}</td>

        <td>${fmt(item.sellingPrice)}</td>

        <td class="text-center">
          <span class="sku-chip">${variantCount}</span>
        </td>

        <td>
          <span class="badge ${st.cls}">${st.label}</span>
        </td>

        <td>
          <div style="display:flex;gap:4px;">
            <button class="btn btn-ghost info btn-icon item-edit-btn" data-id="${item.id}">
              <i class="bi bi-pencil"></i>
            </button>

            <button class="btn btn-ghost btn-icon item-barcode-btn" data-id="${item.id}" title="Print Barcode Label">
              <i class="bi bi-upc-scan"></i>
            </button>

            <button class="btn btn-ghost success btn-icon item-stock-btn" data-id="${item.id}">
              <i class="bi bi-boxes"></i>
            </button>

            <button class="btn btn-ghost danger btn-icon item-del-btn" data-id="${item.id}">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `);

    if (isExpanded) {
      $tbody.append(renderVariantDetailsRow(item));
    }

  });

  $('#itemsPaginationInfo').text(
    `Showing page ${meta?.currentPage || 1} of ${meta?.lastPage || 1} (${meta?.total || 0} items)`
  );

  renderPaginationBtns($('#itemsPaginationBtns'), meta || {
      total: data.length,
      perPage: Config.itemsPerPage,
      currentPage: 1,
      lastPage: 1
  }, (p)=>{
      loadItems(p);
  });

}
  /* ── Populate category dropdowns ── */
  function populateCatDropdowns() {
    console.log('Populating category dropdowns with categories:', Store.categories);
    const $selectors = $('#itemCategorySelect, #itemFilterCat,#stockFilterCat');
    $selectors.each(function () {
      const isFilter = this.id === 'itemFilterCat';
      const cur = $(this).val();
      $(this).empty();
      if (isFilter) $(this).append('<option value="">All Categories</option>');
      else          $(this).append('<option value="">Select Category</option>');
      Store.categories.forEach(c => {
        $(this).append(`<option value="${c.id}" ${parseInt(cur)===c.id?'selected':''}>${esc(c.name)}</option>`);
      });
    });
  }

  /* ── Open Add Modal ── */
  function openAdd() {
    editingId = null;
    $('#itemModalTitle').html('<i class="bi bi-plus-circle"></i> Add New Item');
    $('#itemForm')[0].reset();
    $('#itemVariantList').empty();
    addVariantRow();  // start with one blank variant row
    openModal('itemModal');
  }

  /* ── Open Edit Modal ── */
  function openEdit(id) {
    editingId = id;
    const item = Store.getItem(id);
    if (!item) return;
    $('#itemModalTitle').html('<i class="bi bi-pencil"></i> Edit Item');
    $('#itemName').val(item.name);
    $('#itemSKU').val(item.sku);
    $('#itemCategorySelect').val(item.categoryId);
    $('#itemBrand').val(item.brand);
    $('#itemCostPrice').val(item.costPrice);
    $('#itemSellingPrice').val(item.sellingPrice);
    $('#itemDescription').val(item.description);
    // Variants
    $('#itemVariantList').empty();
    item.variants.forEach(v => addVariantRow(v.size, v.color, v.stock));
    openModal('itemModal');
  }

  /* ── Add variant row ── */
  function addVariantRow(size = '', color = '', stock = 0) {
    const rowId = Date.now() + Math.random();
    $('#itemVariantList').append(`
      <div class="variant-row" data-row="${rowId}">
        <div>
          <input type="text" class="form-control vr-size" placeholder="Size (e.g. M, 32, XS)" value="${esc(size)}"/>
        </div>
        <div>
          <input type="text" class="form-control vr-color" placeholder="Color (e.g. White)" value="${esc(color)}"/>
        </div>
        <div style="display:grid;grid-template-columns:1fr auto;gap:6px;align-items:center;">
          <input type="number" class="form-control vr-stock" placeholder="Stock" min="0" value="${stock}" data-type="number"/>
          <button class="vr-remove" data-row="${rowId}" title="Remove"><i class="bi bi-x"></i></button>
        </div>
      </div>`);
  }

  /* ── Save item (add or edit) ── */
  async function saveItem() {
    const $form = $('#itemForm');
    if (!validateForm($form)) return;

    // Build variants
    const variants = [];
    $('#itemVariantList .variant-row').each(function () {
      const size  = $(this).find('.vr-size').val().trim();
      const color = $(this).find('.vr-color').val().trim();
      const stock = parseInt($(this).find('.vr-stock').val()) || 0;
      if (size) variants.push({ size, color: color || 'N/A', stock });
    });

    const payload = {
      name:         $('#itemName').val().trim(),
      sku:          $('#itemSKU').val().trim(),
      categoryId:   parseInt($('#itemCategorySelect').val()),
      brand:        $('#itemBrand').val().trim(),
      costPrice:    parseFloat($('#itemCostPrice').val()),
      sellingPrice: parseFloat($('#itemSellingPrice').val()),
      description:  $('#itemDescription').val().trim(),
      emoji:        '👔',  // could be derived from category
      variants,
    };

    if (editingId) {
      // PUT /api/v1/inventory/items/{id}
      API.put(`/items/${editingId}`, payload, function (res) {
        if (!res?.success) {
          toast(res?.message || 'Failed to update item.', 'danger');
          return;
        }

        closeModal('itemModal');
        loadItems(currentPage);
        StockMgr.loadStock(); // Refresh stock data for variants
        HistoryMgr.init(); // Refresh ledger history
        refreshStats();
        toast(res.message || 'Item updated successfully!', 'success');
      });
      return;
    }

    // POST /api/v1/inventory/items
    try {
      const res = await API.post('/items', payload);
      if (!res?.success) {
        toast(res?.message || 'Failed to add item.', 'danger');
        return;
      }

      closeModal('itemModal');
      loadItems(currentPage);
      refreshStats();
      toast(res.message || 'Item added successfully!', 'success');
    } catch (err) {
      toast(err?.message || 'Server error', 'danger');
    }
  }

  /* ── Delete item ── */
  function deleteItem(id) {
    if (!confirm('Delete this item and all its variants? This cannot be undone.')) return;
    API.delete(`/items/${id}`);
    Store.items = Store.items.filter(i => i.id !== id);
    toast('Item deleted.', 'danger');
    render();
    refreshStats();
  }

  function printBarcodeLabel(itemId) {
    const item = Store.items.find(i => i.id === itemId);
    if (!item) {
      toast('Item not found for barcode print.', 'warning');
      return;
    }

    const labels = (item.variants || [])
      .filter(v => String(v.barcode || '').trim() !== '')
      .map(v => {
        const barcodeValue = String(v.barcode).trim();
        return {
          itemName: item.name || '',
          variantName: getVariantLabel(v),
          barcodeValue,
          barcodeSvg: buildBarcodeSvg(barcodeValue)
        };
      });

    if (!labels.length) {
      toast('No variant barcode found in barcode field for this item.', 'warning');
      return;
    }

    printBarcodeLabels(labels);
  }

  /* ── Init ── */
  function init() {
    loadCategories();
    // populateCatDropdowns();
    render();

    // Add item btn
    $(document).on('click', '#btnAddItem', openAdd);

    // Edit btn
    $(document).on('click', '.item-edit-btn', function () { openEdit(parseInt($(this).data('id'))); });

    // Delete btn
    $(document).on('click', '.item-del-btn', function () { deleteItem(parseInt($(this).data('id'))); });

    // Print barcode label button
    $(document).on('click', '.item-barcode-btn', function () { printBarcodeLabel(parseInt($(this).data('id'))); });

    // Expand/collapse variants in table
    $(document).on('click', '.item-expand-btn', function () {
      const itemId = parseInt($(this).data('id'));
      if (!itemId) return;

      if (expandedRows.has(itemId)) expandedRows.delete(itemId);
      else expandedRows.add(itemId);
      render();
    });

    // Print one specific variant barcode
    $(document).on('click', '.item-variant-barcode-btn', function () {
      const itemId = parseInt($(this).data('id'));
      const variantId = parseInt($(this).data('variant-id'));
      if (!itemId || !variantId) return;
      printVariantBarcode(itemId, variantId);
    });

    // Stock operations shortcut → go to stock page and filter by item
    $(document).on('click', '.item-stock-btn', function () {
      const id = parseInt($(this).data('id'));
      showPage('stock');
      StockMgr.filterByItem(id);
    });

    // Add variant row button
    $(document).on('click', '#btnAddVariant', () => addVariantRow());

    // Remove variant row
    $(document).on('click', '.vr-remove', function () {
      $(this).closest('.variant-row').remove();
    });

    // Save
    $(document).on('click', '#btnSaveItem', saveItem);

    // Search
    $('#itemSearchInput').on('input', function () {
      filterSearch = $(this).val().trim();
      currentPage  = 1;
      loadItems(1);
    });

    // Category filter
    $('#itemFilterCat').on('change', function () {
      filterCat   = $(this).val();
      currentPage = 1;
      loadItems(1);
    });

    // Column sort
    $(document).on('click', '.item-sort', function () {
      const col = $(this).data('sort');
      if (sortCol === col) sortDir = sortDir === 'asc' ? 'desc' : 'asc';
      else { sortCol = col; sortDir = 'asc'; }
      render();
    });
  }

  return { init, render, populateCatDropdowns ,loadItems};
})();
