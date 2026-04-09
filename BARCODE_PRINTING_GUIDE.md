# 🏷️ Barcode Printing Module - Optional Enhancement

This document describes how to add barcode printing capability to the ShopInventory system.

## Overview

Add barcode labels to items/variants for:
- Physical shelf labeling
- Product identification
- Inventory tracking
- POS scanning preparation


---

## 📦 Setup

### 1. Add Barcode Library

Add to `resources/views/layout.blade.php` in the `<head>`:
```html
<!-- JsBarcode for barcode generation -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
```

### 2. Create Barcode Printing Function

Add to `public/js/app.js` or create `public/js/barcode.js`:

```javascript
/**
 * Barcode Printing Module
 * Uses JsBarcode library to generate and print barcodes
 */

const BarcodeModule = {
  /**
   * Generate and print barcode label for a variant
   */
  printBarcode(variant) {
    const html = `
      <div id="barcodeContent" style="
        width: 100mm;
        height: 60mm;
        margin: 5mm;
        padding: 5mm;
        border: 1px solid #ccc;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        page-break-after: always;
        font-family: Arial, sans-serif;
      ">
        <div style="font-weight: bold; margin-bottom: 5px; text-align: center;">
          ${variant.itemName}
        </div>
        <div style="font-size: 10px; margin-bottom: 8px; text-align: center;">
          ${variant.size} / ${variant.color}
        </div>
        <svg id="barcode"></svg>
        <div style="font-size: 10px; margin-top: 5px; font-family: monospace;">
          ${variant.barcode}
        </div>
        <div style="font-size: 9px; color: #666; margin-top: 5px;">
          Rs ${variant.sellingPrice}
        </div>
      </div>
    `;

    // Open print window
    const printWindow = window.open('', 'PRINT', 'width=800,height=600');
    printWindow.document.write(`
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>Print Barcode</title>
        <style>
          @media print {
            body { margin: 0; padding: 0; }
            #barcodeContent { break-after: page; }
          }
          body { font-family: Arial, sans-serif; }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
      </head>
      <body>
        ${html}
        <script>
          JsBarcode("#barcode", "${variant.barcode}", {
            format: "CODE128",
            width: 2,
            height: 50,
            displayValue: false
          });
          window.print();
        <\/script>
      </body>
      </html>
    `);
    printWindow.document.close();
  },

  /**
   * Print multiple barcodes for a batch
   */
  printBatchBarcodes(variants) {
    let html = `<!DOCTYPE html>
      <html>
      <head>
        <meta charset="UTF-8">
        <title>Print Barcodes Batch</title>
        <style>
          @page { margin: 0; size: A4; }
          @media print {
            body { margin: 0; padding: 0; }
            .barcode-label { page-break-after: always; }
          }
          body { font-family: Arial, sans-serif; padding: 0; margin: 0; }
          .barcode-label {
            width: 100mm;
            height: 60mm;
            margin: 5mm;
            padding: 5mm;
            border: 1px solid #ccc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
          }
          .barcode-label-name { font-weight: bold; margin-bottom: 5px; }
          .barcode-label-variant { font-size: 11px; margin-bottom: 8px; }
          .barcode-label-price { font-size: 10px; color: #666; margin-top: 5px; }
          .barcode-label-code { font-size: 10px; margin-top: 5px; font-family: monospace; }
        </style>
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
      </head>
      <body>`;

    variants.forEach((v, idx) => {
      html += `
        <div class="barcode-label">
          <div class="barcode-label-name">${v.itemName}</div>
          <div class="barcode-label-variant">${v.size} / ${v.color}</div>
          <svg id="barcode-${idx}"></svg>
          <div class="barcode-label-code">${v.barcode}</div>
          <div class="barcode-label-price">Rs ${v.sellingPrice}</div>
        </div>
      `;
    });

    html += `
        <script>
          const variants = ${JSON.stringify(variants)};
          variants.forEach((v, idx) => {
            JsBarcode("#barcode-" + idx, v.barcode, {
              format: "CODE128",
              width: 2,
              height: 50,
              displayValue: false
            });
          });
          window.print();
        <\/script>
      </body>
      </html>
    `;

    const printWindow = window.open('', 'PRINT_BATCH', 'width=800,height=600');
    printWindow.document.write(html);
    printWindow.document.close();
  }
};
```

### 3. Add Print Button to Item List

In `resources/views/dashboard.blade.php`, in the items table row, add:

```html
<button class="btn btn-sm btn-outline" onclick="BarcodeModule.printBarcode({
  itemName: '${item.name}',
  size: '${variant.size}',
  color: '${variant.color}',
  barcode: '${variant.barcode}',
  sellingPrice: ${item.sellingPrice}
})" title="Print Barcode Label">
  <i class="bi bi-printer"></i> Print
</button>
```

### 4. Database Barcode Auto-Generation

If variants don't have barcodes, auto-generate them in SalesController:

```php
use App\Helpers\BarcodeHelper;

// In SalesController or migration
$variant = ItemVariant::find($id);
if (!$variant->barcode) {
  $variant->barcode = BarcodeHelper::generate($variant->id);
  $variant->save();
}
```

---

## 🎨 Barcode Formats

Supported by JsBarcode:

| Format | Use Case | Example |
|--------|----------|---------|
| CODE128 (default) | General purpose | `VAR0000000001` |
| CODE39 | Alphanumeric | `CLT-001-M-BL` |
| EAN13 | Retail standard | `5901234123457` |
| UPC | US retail | `736000291452` |

Change format in BarcodeModule:
```javascript
JsBarcode("#barcode", "${variant.barcode}", {
  format: "EAN13",  // Change this
  width: 2,
  height: 50
});
```

---

## 📋 Barcode Label Templates

### A4 Sheet Template (4 labels per page)
```css
@page { size: A4; margin: 10mm; }
body { display: grid; grid-template-columns: 1fr 1fr; gap: 10mm; }
.barcode-label { width: 90mm; height: 60mm; }
```

### Thermal Printer Template (58mm)
```css
body { width: 58mm; margin: 0; padding: 0; }
.barcode-label { width: 58mm; height: 40mm; }
```

### Small Label Template (30x30mm)
```css
body { width: 30mm; }
.barcode-label { width: 30mm; height: 30mm; font-size: 8px; }
```

---

## 🔌 Integration with POS

After barcode is printed and applied to item:

1. Cashier scans barcode at POS
2. SalesMgr.scanBarcode() looks up variant
3. System confirms variant found
4. Item added to cart

To verify barcodes work:
```javascript
// Test in console
SalesMgr.scanBarcode('VAR0000000001');
// Should add item to cart or show "not found"
```

---

## 🐛 Troubleshooting

**Issue: Barcode SVG not rendering**
- Solution: Ensure JsBarcode.js loaded: `<script src="...JsBarcode..."></script>`

**Issue: Print dialog shows blank**
- Solution: Check barcode value is valid string
- Check format matches barcode content

**Issue: Multiple barcodes overlap**
- Solution: Ensure `page-break-after: always;` in CSS
- Or use `<hr style="page-break-after: always;">` between labels

**Issue: Barcode not scannable**
- Solution: Increase barcode width/height
- Use simpler format (CODE128 most compatible)
- Ensure print quality is good (300 DPI minimum)

---

## 📱 Mobile-Friendly Barcode Printing

For mobile devices, recommend:
1. Save barcode as image: `canvas.toBlob()` → save
2. Print from desktop app
3. Or use thermal printer WebAPI:

```javascript
// For Bluetooth thermal printers
async function printToThermalPrinter(variant) {
  const device = await navigator.usb.requestDevice({
    filters: [{ vendorId: 0x0456 }] // Brother printer
  });
  // ... Bluetooth printing protocol
}
```

---

## 📊 Barcode Generation Strategy

### Option 1: Manual Assignment
- User enters custom barcode for each variant
- Flexible, but time-consuming

### Option 2: Auto-Generate on Variant Creation
```php
// In VariantController->store()
$variant->barcode = BarcodeHelper::generate($variant->id);
$variant->save();
// Result: VAR0000000005
```

### Option 3: SKU-Based
```php
// Generate from SKU
$barcode = strtoupper(str_replace('-', '', $item->sku) . 
          str_pad($variant->id, 4, '0', STR_PAD_LEFT));
// Result: CLT0010005
```

### Option 4: User-Friendly Format
```php
// Generate readable format
$barcode = sprintf('%s-%s-%s-%03d',
  strtoupper(substr($item->name, 0, 3)),
  $variant->size,
  substr($variant->color, 0, 2),
  $variant->id
);
// Result: OXF-M-BL-001
```

---

## 🎁 Advanced Features

### QR Code Alternative
```javascript
// Use QR code instead of barcode
JsBarcode("#barcode", variant.barcode, {
  format: "QR",
  width: 2,
  height: 2
});
```

### Inventory Check-In
Print barcodes for receiving:
```javascript
// Print barcodes + qty for receiving
${variant.itemName}
Qty: ___________
Received By: ___________
Date: ___________
```

### Stock Movement Labels
Print barcode with stock info:
```javascript
Current Stock: ${variant.stock}
Reorder Level: ${variant.reorderLevel}
Location: ${variant.location}
```

---

## 📚 Resources

- JsBarcode Docs: https://jsbarcode.com
- Barcode Formats: https://en.wikipedia.org/wiki/Barcode
- Print CSS: https://www.smashingmagazine.com/2015/01/designing-for-print-with-css/

---

**Version: 1.0**  
**Status: Optional Enhancement** 🏷️
