/**
 * Sales Module (POS System)
 * Handles barcode scanning, cart management, checkout with IRD compliance
 */

const SalesMgr = {
  cart: [],
  allVariants: [],
  variantIndex: {}, // Quick lookup by barcode and ID
  searchDebounceTimer: null,
  searchHighlightedIdx: -1, // Track keyboard navigation in search results
  reportDays: 30,
  reportUserId: '',
  reportFromDate: '',
  reportToDate: '',
  reportUsers: [],
  selectedBillId: null,

  init() {
    this.setupEventListeners();
    this.loadVariants();
    this.initStatementDefaults();
    this.updateSummary();
    this.loadStatementReport();
  },

  setupEventListeners() {
    // Barcode input
    $('#posBarcodeInput').on('keypress', (e) => {
      if (e.which === 13) { // ENTER key
        e.preventDefault();
        const barcode = $('#posBarcodeInput').val().trim();
        if (barcode) {
          this.scanBarcode(barcode);
          $('#posBarcodeInput').val('').focus();
        }
      }
    });

    // Item name search with keyboard navigation
    $('#posItemSearch').on('input', (e) => {
      const searchTerm = $(e.target).val().trim();
      clearTimeout(this.searchDebounceTimer);
      this.searchHighlightedIdx = -1; // Reset highlight on new input

      this.searchDebounceTimer = setTimeout(() => {
        if (searchTerm.length >= 2) {
          this.searchVariants(searchTerm);
        } else {
          $('#posSearchResults').empty();
        }
      }, 180);
    });

    // Keyboard navigation for search results (Arrow keys, Enter, Escape)
    $('#posItemSearch').on('keydown', (e) => {
      const $results = $('#posSearchResults');
      const resultItems = $results.find('.search-result-item');
      
      if (resultItems.length === 0) return;

      switch(e.key) {
        case 'ArrowDown':
          e.preventDefault();
          this.searchHighlightedIdx = Math.min(this.searchHighlightedIdx + 1, resultItems.length - 1);
          this.updateSearchHighlight(resultItems);
          break;
        
        case 'ArrowUp':
          e.preventDefault();
          this.searchHighlightedIdx = Math.max(this.searchHighlightedIdx - 1, -1);
          this.updateSearchHighlight(resultItems);
          break;
        
        case 'Enter':
          e.preventDefault();
          if (this.searchHighlightedIdx >= 0) {
            const variantId = $(resultItems[this.searchHighlightedIdx]).data('variant-id');
            this.addVariantFromSearch(variantId);
          }
          break;
        
        case 'Escape':
          e.preventDefault();
          $('#posSearchResults').empty();
          this.searchHighlightedIdx = -1;
          break;
      }
    });

    // Close search results when clicking outside
    $(document).on('click', (e) => {
      if (!$(e.target).closest('#posSearchContainer').length) {
        $('#posSearchResults').empty();
        this.searchHighlightedIdx = -1;
      }
    });

    // Discount percentage
    $('#posDiscountPercent').on('change', () => {
      this.updateSummary();
    });

    // Quantity change in cart
    $(document).on('change', '.pos-item-qty', (e) => {
      const idx = $(e.target).data('idx');
      const qty = parseInt($(e.target).val()) || 0;
      const item = this.cart[idx];
      const maxQty = item?.variant?.stock || 0;

      if (qty > 0 && qty <= maxQty) {
        this.cart[idx].quantity = qty;
      } else if (qty > maxQty) {
        const remaining = maxQty - item.quantity;
        $(e.target).val(this.cart[idx].quantity);
        toast(`Cannot exceed stock. Only ${remaining} more available.`, 'warning');
      } else {
        $(e.target).val(this.cart[idx].quantity);
      }
      this.renderCart();
      this.updateSummary();
    });

    // Delete key to remove cart item (when quantity input is focused)
    $(document).on('keydown', '.pos-item-qty', (e) => {
      const idx = $(e.target).data('idx');
      
      if (e.key === 'Delete') {
        e.preventDefault();
        this.removeFromCart(idx);
      } else if (e.key === 'ArrowUp') {
        // Increment quantity with Up arrow
        e.preventDefault();
        const currentQty = parseInt($(e.target).val()) || 0;
        const item = this.cart[idx];
        const maxQty = item?.variant?.stock || 0;
        if (currentQty < maxQty) {
          $(e.target).val(currentQty + 1).trigger('change');
        }
      } else if (e.key === 'ArrowDown') {
        // Decrement quantity with Down arrow
        e.preventDefault();
        const currentQty = parseInt($(e.target).val()) || 0;
        if (currentQty > 1) {
          $(e.target).val(currentQty - 1).trigger('change');
        }
      }
    });

    // Remove from cart (click or keyboard)
    $(document).on('click', '.pos-item-remove', (e) => {
      e.preventDefault();
      const idx = $(e.currentTarget).data('idx');
      this.removeFromCart(idx);
    });

    // Clear cart
    $('#posCartClear').on('click', () => {
      if (confirm('Clear the entire cart?')) {
        this.cart = [];
        this.renderCart();
        this.updateSummary();
      }
    });

    // Checkout
    $('#posCheckoutBtn').on('click', () => {
      this.checkout();
    });

    $('#posCheckoutTopBtn, #posCheckoutQuickBtn').on('click', () => {
      this.checkout();
    });

    // Reset/New Sale
    $('#posResetBtn').on('click', () => {
      this.reset();
    });

    $('#salesReportUserFilter').on('change', () => {
      this.reportUserId = $('#salesReportUserFilter').val() || '';
      this.loadStatementReport();
    });

    $('#salesReportDateFrom, #salesReportDateTo').on('change', () => {
      this.reportFromDate = $('#salesReportDateFrom').val() || '';
      this.reportToDate = $('#salesReportDateTo').val() || '';
      this.loadStatementReport();
    });

    $('#salesReportRefresh').on('click', () => {
      this.loadStatementReport();
    });

    $(document).on('click', '.sales-bill-row', (e) => {
      e.preventDefault();
      const billId = $(e.currentTarget).data('bill-id');
      if (billId) {
        this.loadBillDetails(billId);
      }
    });
  },

  /**
   * Highlight search result for keyboard navigation
   */
  updateSearchHighlight(resultItems) {
    $(resultItems).removeClass('highlighted');
    if (this.searchHighlightedIdx >= 0) {
      $(resultItems[this.searchHighlightedIdx]).addClass('highlighted');
      // Scroll into view
      resultItems[this.searchHighlightedIdx].scrollIntoView({ block: 'nearest' });
    }
  },

  async loadVariants() {
    try {
      const variants = await new Promise((resolve) => {
        API.get('/variants?per_page=1000', (data) => {
          resolve(data.data || []);
        });
      });

      this.allVariants = variants;

      // Build quick lookup index
      variants.forEach((v) => {
        if (v.barcode) {
          this.variantIndex[v.barcode] = v;
        }
        this.variantIndex['id_' + v.id] = v;
      });

      toast('Variants loaded. Ready to scan or search.', 'success');
    } catch (e) {
      toast('Failed to load variants', 'danger');
    }
  },

  /**
   * Search variants by item name
   */
  async searchVariants(searchTerm) {
    if (!searchTerm || searchTerm.length < 2) {
      $('#posSearchResults').empty();
      return;
    }

    try {
      const results = await new Promise((resolve) => {
        API.get(`/variants?search=${encodeURIComponent(searchTerm)}&per_page=20`, (data) => {
          resolve(data.data || []);
        });
      });

      this.displaySearchResults(results);
    } catch (e) {
      toast('Search failed', 'danger');
    }
  },

  /**
   * Display search results in dropdown
   */
  displaySearchResults(variants) {
    const resultsDiv = $('#posSearchResults');

    if (variants.length === 0) {
      resultsDiv.html('<div class="search-result-item" style="justify-content: center; color: var(--text-muted);">No items found</div>');
      return;
    }

    const html = variants
      .map(
        (v, idx) => `
      <div class="search-result-item" data-variant-id="${v.id}" data-index="${idx}" style="cursor: pointer;">
        <div class="search-result-info">
          <div class="search-result-name">${v.itemName}</div>
          <div class="search-result-meta">
            ${v.size ? `Size: ${v.size}` : ''} ${v.color ? `/ ${v.color}` : ''} ${v.sku ? `• SKU: ${v.sku}` : ''}
          </div>
        </div>
        <div class="search-result-right">
          <div class="search-result-price">Rs ${(v.sellingPrice || 0).toFixed(2)}</div>
          <div class="search-result-stock" style="background-color: ${v.stock > 0 ? 'var(--success-bg)' : 'var(--danger-bg)'}; color: ${v.stock > 0 ? 'var(--success)' : 'var(--danger)'};">
            ${v.stock} in stock
          </div>
        </div>
      </div>
    `
      )
      .join('');

    resultsDiv.html(html);

    // Add keyboard/mouse interactions to search results
    resultsDiv.find('.search-result-item').on('click', (e) => {
      const variantId = $(e.currentTarget).data('variant-id');
      this.addVariantFromSearch(variantId);
    });

    // Sync mouse hover with keyboard highlight
    resultsDiv.find('.search-result-item').on('mouseover', (e) => {
      this.searchHighlightedIdx = $(e.currentTarget).data('index');
      this.updateSearchHighlight(resultsDiv.find('.search-result-item'));
    });

    resultsDiv.find('.search-result-item').on('mouseleave', () => {
      $(resultsDiv.find('.search-result-item')).removeClass('highlighted');
    });
  },

  /**
   * Add variant from search results
   */
  addVariantFromSearch(variantId) {
    const variant = this.variantIndex['id_' + variantId];
    if (!variant) {
      toast('Variant not found', 'warning');
      return;
    }

    // Check stock
    if (variant.stock <= 0) {
      toast(`Out of stock: ${variant.itemName}`, 'danger');
      return;
    }

    // Check if already in cart
    const existingIdx = this.cart.findIndex((item) => item.variant.id === variant.id);

    if (existingIdx >= 0) {
      const item = this.cart[existingIdx];
      if (item.quantity < variant.stock) {
        item.quantity++;
        toast(`Qty ↑ ${variant.size}/${variant.color}`, 'info');
      } else {
        toast(`Max stock reached`, 'warning');
      }
    } else {
      this.cart.push({
        variant,
        quantity: 1,
        price: variant.sellingPrice,
      });
      toast(`Added: ${variant.itemName}`, 'success');
    }

    // Clear search
    $('#posItemSearch').val('');
    $('#posSearchResults').empty();

    this.renderCart();
    this.updateSummary();
  },

  async scanBarcode(barcode) {
    // Query database for variant by barcode
    try {
      const variants = await new Promise((resolve, reject) => {
        API.get(`/variants?search=${encodeURIComponent(barcode)}&per_page=100`, (data) => {
          resolve(data.data || []);
        });
      });

      // Find exact match by barcode
      const variant = variants.find(v => v.barcode === barcode);

      if (!variant) {
        toast(`Barcode not found: ${barcode}`, 'warning');
        return;
      }

      // Check stock
      if (variant.stock <= 0) {
        toast(`Out of stock: ${variant.itemName || 'Item'}`, 'danger');
        return;
      }

      // Check if already in cart
      const existingIdx = this.cart.findIndex((item) => item.variant.id === variant.id);

      if (existingIdx >= 0) {
        // Increase quantity
        const item = this.cart[existingIdx];
        if (item.quantity < variant.stock) {
          item.quantity++;
          toast(`Qty ↑ ${variant.size}/${variant.color}`, 'info');
        } else {
          toast(`Max stock reached for this variant`, 'warning');
        }
      } else {
        // Add to cart
        this.cart.push({
          variant,
          quantity: 1,
          price: variant.sellingPrice,
        });
        toast(`Added: ${variant.itemName} (${variant.size}/${variant.color})`, 'success');
      }

      this.renderCart();
      this.updateSummary();
    } catch (e) {
      toast('Barcode lookup failed', 'danger');
      console.error('Scan error:', e);
    }
  },

  removeFromCart(idx) {
    if (idx >= 0 && idx < this.cart.length) {
      const item = this.cart[idx];
      toast(`Removed: ${item.variant.itemName}`, 'info');
      this.cart.splice(idx, 1);
      this.renderCart();
      this.updateSummary();
    }
  },

  renderCart() {
    const body = $('#posCartBody');

    if (this.cart.length === 0) {
      body.html(
        `<tr id="posCartEmpty">
          <td colspan="7" style="text-align:center;padding:24px;color:var(--text-muted);">
            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
            Cart is empty. Start scanning barcodes.
          </td>
        </tr>`
      );
      return;
    }

    body.html(
      this.cart
        .map((item, idx) => {
          const v = item.variant;
          const lineTotal = item.price * item.quantity;
          const remainingStock = v.stock - item.quantity;
          const stockBadgeClass = remainingStock <= 0 ? 'badge-danger' : (remainingStock <= 5 ? 'badge-warning' : 'badge-success');
          return `
        <tr>
          <td style="font-weight:600;">${v.itemName || 'Unknown'}</td>
          <td><small>${v.size} / ${v.color}</small></td>
          <td style="text-align:center;">
            <input type="number" class="pos-item-qty form-control"
              value="${item.quantity}" min="1" max="${v.stock}"
              data-idx="${idx}" style="width:60px;text-align:center;padding:4px;"/>
          </td>
          <td style="text-align:center;">
            <span class="badge ${stockBadgeClass}" style="padding:6px 10px;font-size:.85rem;">
              ${remainingStock} left
            </span>
          </td>
          <td style="text-align:right;"><small>Rs. ${this.formatMoney(item.price)}</small></td>
          <td style="text-align:right;font-weight:600;">Rs. ${this.formatMoney(lineTotal)}</td>
          <td style="text-align:center;">
            <button class="btn btn-sm btn-outline pos-item-remove" data-idx="${idx}">
              <i class="bi bi-x"></i>
            </button>
          </td>
        </tr>
      `;
        })
        .join('')
    );
  },

  updateSummary() {
    // Calculate subtotal
    let subtotal = 0;
    let totalQty = 0;

    this.cart.forEach((item) => {
      const lineTotal = item.price * item.quantity;
      subtotal += lineTotal;
      totalQty += item.quantity;
    });

    // Discount
    const discountPercent = parseFloat($('#posDiscountPercent').val()) || 0;
    const discountAmount = (subtotal * discountPercent) / 100;
    const taxableAmount = subtotal - discountAmount;

    // VAT (13%)
    const vat = taxableAmount * 0.13;
    const grandTotal = taxableAmount + vat;

    // Update display
    $('[id="posSummaryItems"]').text(this.cart.length);
    $('[id="posCartCountBadge"]').text(this.cart.length);
    $('[id="posSummaryQty"]').text(totalQty);
    $('#posSummarySubtotal').text('Rs. ' + this.formatMoney(subtotal));
    $('#posSummaryDiscount').text('Rs. ' + this.formatMoney(discountAmount));
    $('#posSummaryTaxable').text('Rs. ' + this.formatMoney(taxableAmount));
    $('#posSummaryVat').text('Rs. ' + this.formatMoney(vat));
    $('#posSummaryGrandTotal').text('Rs. ' + this.formatMoney(grandTotal));
    $('#posSummaryGrandTotalMini').text('Rs. ' + this.formatMoney(grandTotal));
  },

  formatMoney(num) {
    return parseFloat(num).toFixed(2);
  },

  initStatementDefaults() {
    const today = new Date();
    const end = today.toISOString().slice(0, 10);
    const start = new Date(today);
    start.setDate(start.getDate() - 29);
    const begin = start.toISOString().slice(0, 10);

    this.reportFromDate = this.reportFromDate || begin;
    this.reportToDate = this.reportToDate || end;

    if ($('#salesReportDateFrom').length) {
      $('#salesReportDateFrom').val(this.reportFromDate);
    }
    if ($('#salesReportDateTo').length) {
      $('#salesReportDateTo').val(this.reportToDate);
    }
  },

  async loadStatementReport() {
    const query = new URLSearchParams({
      days: String(this.reportDays),
    });

    if (this.reportFromDate) {
      query.set('from_date', this.reportFromDate);
    }

    if (this.reportToDate) {
      query.set('to_date', this.reportToDate);
    }

    if (this.reportUserId) {
      query.set('user_id', this.reportUserId);
    }

    try {
      const data = await new Promise((resolve, reject) => {
        $.ajax({
          url: `${Config.apiBase}/sales/statement?${query.toString()}`,
          method: 'GET',
          headers: {
            Authorization: 'Bearer ' + API.getToken(),
          },
          success: (res) => resolve(res),
          error: (xhr) => reject(xhr.responseJSON || { message: 'Failed to load statement report' }),
        });
      });

      if (!data || !data.success) {
        toast(data?.message || 'Failed to load statement report', 'danger');
        return;
      }

      this.reportUsers = data.data?.users || [];
      this.renderStatementReport(data.data || {});

      if (this.selectedBillId) {
        const stillVisible = (data.data?.bills || []).some((bill) => String(bill.id) === String(this.selectedBillId));
        if (!stillVisible) {
          this.selectedBillId = null;
          this.renderBillDetails(null);
        }
      }
    } catch (e) {
      toast('Failed to load statement report', 'danger');
    }
  },

  renderStatementReport(reportData) {
    const users = reportData.users || [];
    const summary = reportData.summary || {};
    const datewise = reportData.datewise || [];
    const userwise = reportData.userwise || [];
    const bills = reportData.bills || [];

    const currentUserId = this.reportUserId;
    const userOptions = ['<option value="">All Users</option>']
      .concat(users.map((user) => {
        const selected = String(user.id) === String(currentUserId) ? 'selected' : '';
        const label = user.username ? `${user.name} (@${user.username})` : user.name;
        return `<option value="${user.id}" ${selected}>${label}</option>`;
      }))
      .join('');

    $('#salesReportUserFilter').html(userOptions);
    $('#salesReportPeriod').text(`${reportData.filters?.fromDate || '—'} to ${reportData.filters?.toDate || '—'}`);
    $('#salesReportBills').text(summary.bills || 0);
    $('#salesReportSubtotal').text(`Rs. ${this.formatMoney(summary.subTotal || 0)}`);
    $('#salesReportDiscount').text(`Rs. ${this.formatMoney(summary.discountAmount || 0)}`);
    $('#salesReportVat').text(`Rs. ${this.formatMoney(summary.vat || 0)}`);
    $('#salesReportGrandTotal').text(`Rs. ${this.formatMoney(summary.grandTotal || 0)}`);

    const datewiseBody = $('#salesReportDatewiseBody');
    if (datewise.length === 0) {
      datewiseBody.html('<tr><td colspan="6" style="text-align:center;padding:18px;color:var(--text-muted);">No sales found for this period.</td></tr>');
    } else {
      datewiseBody.html(datewise.map((row) => `
        <tr>
          <td style="font-weight:600;">${row.date}</td>
          <td style="text-align:center;">${row.bills}</td>
          <td style="text-align:right;">Rs. ${this.formatMoney(row.subTotal || 0)}</td>
          <td style="text-align:right;">Rs. ${this.formatMoney(row.discountAmount || 0)}</td>
          <td style="text-align:right;">Rs. ${this.formatMoney(row.vat || 0)}</td>
          <td style="text-align:right;font-weight:700;">Rs. ${this.formatMoney(row.grandTotal || 0)}</td>
        </tr>
      `).join(''));
    }

    const userwiseBody = $('#salesReportUserwiseBody');
    if (userwise.length === 0) {
      userwiseBody.html('<tr><td colspan="6" style="text-align:center;padding:18px;color:var(--text-muted);">No user summary available.</td></tr>');
    } else {
      userwiseBody.html(userwise.map((row) => {
        const displayName = row.username ? `${row.userName} (@${row.username})` : row.userName;
        return `
          <tr>
            <td style="font-weight:600;">${displayName}</td>
            <td style="text-align:center;">${row.bills}</td>
            <td style="text-align:right;">Rs. ${this.formatMoney(row.subTotal || 0)}</td>
            <td style="text-align:right;">Rs. ${this.formatMoney(row.discountAmount || 0)}</td>
            <td style="text-align:right;">Rs. ${this.formatMoney(row.vat || 0)}</td>
            <td style="text-align:right;font-weight:700;">Rs. ${this.formatMoney(row.grandTotal || 0)}</td>
          </tr>
        `;
      }).join(''));
    }

    const billsBody = $('#salesReportBillsBody');
    if (bills.length === 0) {
      billsBody.html('<tr><td colspan="7" style="text-align:center;padding:18px;color:var(--text-muted);">No bills found for this period.</td></tr>');
    } else {
      billsBody.html(bills.map((bill) => {
        const displayUser = bill.username ? `${bill.userName} (@${bill.username})` : bill.userName;
        const activeClass = String(this.selectedBillId) === String(bill.id) ? 'table-active' : '';
        return `
          <tr class="sales-bill-row ${activeClass}" data-bill-id="${bill.id}" style="cursor:pointer;">
            <td style="font-weight:700;">
              <a href="#" class="sales-bill-link" data-bill-id="${bill.id}" style="text-decoration:none;">${bill.billNumber}</a>
            </td>
            <td>${bill.saleDate}</td>
            <td>${displayUser}</td>
            <td>${bill.customerName}</td>
            <td style="text-align:center;">${bill.paymentMethod}</td>
            <td style="text-align:right;font-weight:700;">Rs. ${this.formatMoney(bill.grandTotal || 0)}</td>
            <td><span class="badge bg-secondary">${bill.status}</span></td>
          </tr>
        `;
      }).join(''));
    }
  },

  async loadBillDetails(billId) {
    this.selectedBillId = billId;

    try {
      const data = await new Promise((resolve, reject) => {
        $.ajax({
          url: `${Config.apiBase}/sales/${billId}`,
          method: 'GET',
          headers: {
            Authorization: 'Bearer ' + API.getToken(),
          },
          success: (res) => resolve(res),
          error: (xhr) => reject(xhr.responseJSON || { message: 'Failed to load bill details' }),
        });
      });

      if (!data || !data.success) {
        toast(data?.message || 'Failed to load bill details', 'danger');
        return;
      }

      this.renderBillDetails(data.data || null);
    } catch (e) {
      toast('Failed to load bill details', 'danger');
    }
  },

  renderBillDetails(payload) {
    const metaBody = $('#salesBillDetailsMeta');
    const tableBody = $('#salesBillDetailsBody');

    if (!payload) {
      if (metaBody.length) {
        metaBody.html('<div style="color:var(--text-muted);padding:12px 0;">Select a bill to view item details.</div>');
      }
      if (tableBody.length) {
        tableBody.html('<tr><td colspan="6" style="text-align:center;padding:18px;color:var(--text-muted);">No bill selected.</td></tr>');
      }
      return;
    }

    const sale = payload.sale || {};
    const invoice = payload.invoice || {};
    const items = payload.items || [];

    if (metaBody.length) {
      metaBody.html(`
        <div class="row g-2">
          <div class="col-md-3"><strong>Bill:</strong> ${sale.billNumber || '—'}</div>
          <div class="col-md-3"><strong>Date:</strong> ${sale.saleDate || '—'}</div>
          <div class="col-md-3"><strong>User:</strong> ${sale.createdBy || '—'}</div>
          <div class="col-md-3"><strong>Customer:</strong> ${sale.customerName || 'Walk-in Customer'}</div>
        </div>
        <div class="row g-2 mt-1">
          <div class="col-md-3"><strong>Payment:</strong> ${sale.paymentMethod || '—'}</div>
          <div class="col-md-3"><strong>Subtotal:</strong> Rs. ${this.formatMoney(invoice.subTotal || 0)}</div>
          <div class="col-md-3"><strong>VAT:</strong> Rs. ${this.formatMoney(invoice.vatAmount || 0)}</div>
          <div class="col-md-3"><strong>Grand Total:</strong> Rs. ${this.formatMoney(invoice.grandTotal || 0)}</div>
        </div>
      `);
    }

    if (tableBody.length) {
      tableBody.html(items.map((item, index) => `
        <tr>
          <td>${index + 1}</td>
          <td>${item.variant?.itemName || '—'}</td>
          <td>${item.variant?.size || '—'}</td>
          <td>${item.variant?.color || '—'}</td>
          <td style="text-align:center;">${item.quantity}</td>
          <td style="text-align:right;">Rs. ${this.formatMoney(item.totalPrice || 0)}</td>
        </tr>
      `).join(''));
    }
  },

  async checkout() {
    // Validation
    if (this.cart.length === 0) {
      toast('Cart is empty', 'warning');
      return;
    }

    // Get form data
    const customerName = $('#posCustomerName').val();
    const customerPan = $('#posCustomerPan').val();
    const paymentMethod = $('#posPaymentMethod').val();
    const discountPercent = parseFloat($('#posDiscountPercent').val()) || 0;

    // Calculate final discount amount
    let subtotal = 0;
    this.cart.forEach((item) => {
      subtotal += item.price * item.quantity;
    });
    const discountAmount = (subtotal * discountPercent) / 100;

    // Build request
    const items = this.cart.map((item) => ({
      variantId: item.variant.id,
      quantity: item.quantity,
      priceOverride: item.price,
    }));

    const payload = {
      customerName,
      customerPan,
      paymentMethod,
      discountAmount,
      items,
    };

    try {
      $('#posCheckoutBtn, #posCheckoutTopBtn, #posCheckoutQuickBtn').prop('disabled', true);
      const response = await API.post('/sales', payload);

      if (response.success) {
        const sale = response.data;
        toast(`✓ Sale successful! Bill: ${sale.sale.billNumber}`, 'success');

        // Show invoice
        this.showInvoice(response.data);

        // Refresh stock tables after sale
        if (typeof StockMgr !== 'undefined' && StockMgr.loadStock) {
          StockMgr.loadStock();
        }
        if (typeof HistoryMgr !== 'undefined' && HistoryMgr.load) {
          HistoryMgr.load();
        }

        this.loadStatementReport();

        // Reset form
        setTimeout(() => {
          this.reset();
        }, 2000);
      } else {
        toast(response.message || 'Sale failed', 'danger');
      }
    } catch (err) {
      toast(err.message || 'Error processing sale', 'danger');
    } finally {
      $('#posCheckoutBtn, #posCheckoutTopBtn, #posCheckoutQuickBtn').prop('disabled', false);
    }
  },

  showInvoice(saleData) {
    const invoice = saleData.invoice;

    const invoiceHTML = `
    <div id="posInvoicePrintable" style="max-width:600px;margin:0 auto;padding:20px;font-family:monospace;background:white;border:1px solid #ccc;border-radius:4px;">
      <div style="text-align:center;margin-bottom:20px;">
        <h3 style="margin:0;">INVOICE</h3>
        <small style="color:#666;">IRD Compliant Bill</small>
      </div>

      <div style="border-bottom:1px solid #ddd;padding-bottom:10px;margin-bottom:10px;font-size:.9rem;">
        <div style="display:flex;justify-content:space-between;">
          <div>
            <strong>Bill No.:</strong> ${invoice.billNumber}<br/>
            <strong>FY:</strong> ${invoice.fiscalYear}<br/>
            <strong>Date:</strong> ${invoice.saleDate} ${invoice.saleTime}
          </div>
          <div style="text-align:right;">
            <strong>Payment:</strong> ${invoice.paymentMethod}<br/>
            <strong>Customer:</strong> ${invoice.customerName}<br/>
            ${invoice.customerPan ? `<strong>PAN:</strong> ${invoice.customerPan}<br/>` : ''}
          </div>
        </div>
      </div>

      <table style="width:100%;border-collapse:collapse;font-size:.85rem;margin-bottom:15px;">
        <thead>
          <tr style="border-bottom:2px solid #000;">
            <th style="text-align:left;padding:4px;">Item</th>
            <th style="text-align:center;padding:4px;">Qty</th>
            <th style="text-align:right;padding:4px;">Price</th>
            <th style="text-align:right;padding:4px;">Total</th>
          </tr>
        </thead>
        <tbody>
          ${invoice.items
            .map(
              (item) => `
            <tr>
              <td style="padding:4px;"><small>${item.itemName}<br/>${item.variant}</small></td>
              <td style="text-align:center;padding:4px;">${item.quantity}</td>
              <td style="text-align:right;padding:4px;">Rs. ${item.unitPrice.toFixed(2)}</td>
              <td style="text-align:right;padding:4px;font-weight:600;">Rs. ${item.lineTotal.toFixed(2)}</td>
            </tr>
          `
            )
            .join('')}
        </tbody>
      </table>

      <div style="border-top:1px solid #ddd;border-bottom:2px solid #000;padding:8px 0;font-size:.9rem;">
        <div style="display:flex;justify-content:space-between;">
          <span>Subtotal:</span>
          <span>Rs. ${invoice.subTotal.toFixed(2)}</span>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span>Discount:</span>
          <span>- Rs. ${invoice.totalDiscount.toFixed(2)}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-weight:600;">
          <span>Taxable:</span>
          <span>Rs. ${invoice.taxableAmount.toFixed(2)}</span>
        </div>
        <div style="display:flex;justify-content:space-between;">
          <span>VAT (${invoice.vatRate}):</span>
          <span>Rs. ${invoice.vatAmount.toFixed(2)}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;margin-top:6px;">
          <span>GRAND TOTAL:</span>
          <span>Rs. ${invoice.grandTotal.toFixed(2)}</span>
        </div>
      </div>

      <div style="text-align:center;margin-top:15px;font-size:.75rem;color:#666;">
        <p style="margin:4px 0;">Thank you for shopping with us!</p>
        <p style="margin:4px 0;">IRD Compliant Bill • All prices inclusive of VAT</p>
        <p style="margin:4px 0;"><strong>Bill Number:</strong> ${invoice.billNumber}</p>
      </div>

      <div style="margin-top:20px;display:flex;gap:8px;justify-content:center;">
        <button onclick="SalesMgr.printInvoiceA5()" class="btn btn-primary" style="padding:8px 16px;">Print</button>
        <button onclick="this.closest('.modal-backdrop').remove()" class="btn btn-outline" style="padding:8px 16px;">Close</button>
      </div>
    </div>
    `;

    const modal = document.createElement('div');
    modal.className = 'modal-backdrop';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '9999';
    modal.innerHTML = `
      <div class="modal-box" style="max-width:650px;">
        <div class="modal-head">
          <h5>Sale Invoice</h5>
          <button class="modal-close" onclick="this.closest('.modal-backdrop').remove()">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="modal-body" style="max-height:70vh;overflow-y:auto;">
          ${invoiceHTML}
        </div>
      </div>
    `;
    document.body.appendChild(modal);
  },

  printInvoiceA5() {
    const printable = document.getElementById('posInvoicePrintable');
    if (!printable) {
      window.print();
      return;
    }

    const printWindow = window.open('', '_blank', 'width=900,height=700');
    if (!printWindow) {
      toast('Please allow popups for printing.', 'warning');
      return;
    }

    printWindow.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8" />
        <title>Invoice Print</title>
        <style>
          @page { size: A5 portrait; margin: 8mm; }
          html, body { margin: 0; padding: 0; background: #fff; font-family: monospace; }
          #print-root { width: 100%; }
          #print-root button { display: none !important; }
        </style>
      </head>
      <body>
        <div id="print-root">${printable.innerHTML}</div>
      </body>
      </html>
    `);
    printWindow.document.close();
    printWindow.focus();

    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 250);
  },

  reset() {
    this.cart = [];
    $('#posCustomerName').val('');
    $('#posCustomerPan').val('');
    $('#posDiscountPercent').val('0');
    $('#posPaymentMethod').val('cash');
    this.renderCart();
    this.updateSummary();
    $('#posBarcodeInput').focus();
  },
};

// Initialize on document ready
$(document).ready(() => {
  const initSales = () => {
    if ($('#page-sales').length) {
      SalesMgr.init();
    }
  };

  if (window.AppAuth?.ready) {
    initSales();
    return;
  }

  $(document).one('app:auth-ready', initSales);
});
