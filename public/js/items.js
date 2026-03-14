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

        Store.items = res.data;
        Store.itemsMeta = res.meta;

        ItemMgr.render();
    });
  }
  function render() {

  const data = Store.items;
  const meta = Store.itemsMeta;

  const $tbody = $('#itemsTableBody');
  $tbody.empty();

  if (!data.length) {
    $tbody.html(`
      <tr>
        <td colspan="8">
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
    
    const totalStock   = item.variants.reduce((s,v)=>s+v.stock,0);
    const variantCount = item.variants.length;

    const st = Store.getStockStatus(totalStock);

    $tbody.append(`
      <tr data-id="${item.id}">
        <td>
          <div class="product-cell">
            <div class="product-img">📦</div>
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

  });

  $('#itemsPaginationInfo').text(
    `Showing page ${meta.currentPage} of ${meta.lastPage} (${meta.total} items)`
  );

  renderPaginationBtns($('#itemsPaginationBtns'), meta, (p)=>{
      loadItems(p);
  });

}
  /* ── Populate category dropdowns ── */
  function populateCatDropdowns() {
    console.log('Populating category dropdowns with categories:', Store.categories);
    const $selectors = $('#itemCategorySelect, #itemFilterCat');
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
  function saveItem() {
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
      API.put(`/items/${editingId}`, payload);
      Object.assign(Store.getItem(editingId), payload);
      toast('Item updated successfully!', 'success');
    } else {
      // POST /api/v1/inventory/items
      API.post('/items', payload);
      payload.id = Store._itemNextId++;
      Store.items.push(payload);
      toast('Item added successfully!', 'success');
    }

    closeModal('itemModal');
    render();
    refreshStats();
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
      render();
    });

    // Category filter
    $('#itemFilterCat').on('change', function () {
      filterCat   = $(this).val();
      currentPage = 1;
      render();
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
