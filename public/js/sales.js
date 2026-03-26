/**
 * Sales Module (POS System)
 * Handles barcode scanning, cart management, checkout with IRD compliance
 */

const SalesMgr = {
  cart: [],
  allVariants: [],
  variantIndex: {}, // Quick lookup by barcode and ID
  searchDebounceTimer: null,

  init() {
    this.setupEventListeners();
    this.loadVariants();
    this.updateSummary();
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

    // Item name search
    $('#posItemSearch').on('input', (e) => {
      const searchTerm = $(e.target).val().trim();
      clearTimeout(this.searchDebounceTimer);

      this.searchDebounceTimer = setTimeout(() => {
        if (searchTerm.length >= 2) {
          this.searchVariants(searchTerm);
        } else {
          $('#posSearchResults').empty();
        }
      }, 180);
    });

    // Close search results when clicking outside
    $(document).on('click', (e) => {
      if (!$(e.target).closest('#posSearchContainer').length) {
        $('#posSearchResults').empty();
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
      const maxQty = this.cart[idx]?.variant?.stock || 0;

      if (qty > 0 && qty <= maxQty) {
        this.cart[idx].quantity = qty;
      } else if (qty > maxQty) {
        $(e.target).val(this.cart[idx].quantity);
        toast(`Only ${maxQty} in stock`, 'warning');
      } else {
        $(e.target).val(this.cart[idx].quantity);
      }
      this.updateSummary();
    });

    // Remove from cart
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
        (v) => `
      <div class="search-result-item" data-variant-id="${v.id}" onclick="SalesMgr.addVariantFromSearch(${v.id})">
        <div class="search-result-info">
          <div class="search-result-name">${v.itemName}</div>
          <div class="search-result-meta">
            ${v.size ? `Size: ${v.size}` : ''} ${v.color ? `/ ${v.color}` : ''} ${v.sku ? `• SKU: ${v.sku}` : ''}
          </div>
        </div>
        <div class="search-result-price">Rs ${(v.sellingPrice || 0).toFixed(2)}</div>
        <div class="search-result-stock" style="background-color: ${v.stock > 0 ? 'var(--success-bg)' : 'var(--danger-bg)'}; color: ${v.stock > 0 ? 'var(--success)' : 'var(--danger)'};">
          ${v.stock} in stock
        </div>
      </div>
    `
      )
      .join('');

    resultsDiv.html(html);
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
          <td colspan="6" style="text-align:center;padding:24px;color:var(--text-muted);">
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
          return `
        <tr>
          <td style="font-weight:600;">${v.itemName || 'Unknown'}</td>
          <td><small>${v.size} / ${v.color}</small></td>
          <td style="text-align:center;">
            <input type="number" class="pos-item-qty form-control"
              value="${item.quantity}" min="1" max="${v.stock}"
              data-idx="${idx}" style="width:60px;text-align:center;padding:4px;"/>
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
    $('#posSummaryItems').text(this.cart.length);
    $('#posCartCountBadge').text(this.cart.length);
    $('#posSummaryQty').text(totalQty);
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
    <div style="max-width:600px;margin:0 auto;padding:20px;font-family:monospace;background:white;border:1px solid #ccc;border-radius:4px;">
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
        <button onclick="window.print()" class="btn btn-primary" style="padding:8px 16px;">🖨️ Print</button>
        <button onclick="this.closest('.modal-backdrop').style.display='none'" class="btn btn-outline" style="padding:8px 16px;">Close</button>
      </div>
    </div>
    `;

    // Show in modal
    const modal = document.createElement('div');
    modal.className = 'modal-backdrop';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '9999';
    modal.innerHTML = `
      <div class="modal-box" style="max-width:650px;">
        <div class="modal-head">
          <h5>📋 Sale Invoice</h5>
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
  if ($('#page-sales').length) {
    SalesMgr.init();
  }
});
