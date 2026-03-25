# 🚀 QUICK REFERENCE CARD - Sales Module

## 📝 Installation (5 minutes)

```bash
# 1. Run migrations
php artisan migrate

# 2. Clear cache
php artisan cache:clear

# 3. Test API
bash test-sales-api.sh

# 4. Login to dashboard and navigate to Sales/POS
```

---

## 🎮 Using the POS System

### Basic Flow
```
1. Open Sales/POS from dashboard menu
2. Type or scan barcode → System adds to cart
3. Adjust quantity if needed
4. Set discount % (optional)
5. Enter customer name/PAN (optional)
6. Select payment method
7. Click "Complete Sale"
8. Print invoice
```

### Keyboard Shortcuts
| Key | Action |
|-----|--------|
| ENTER | Add barcode to cart |
| TAB | Navigate fields |
| DELETE | Remove from cart |

---

## 🔧 Key Files

| File | Purpose | Lines |
|------|---------|-------|
| SalesController.php | API logic | 480 |
| sales.js | Frontend logic | 650 |
| FiscalYearHelper.php | Bill generation | 45 |
| Sales migration | Database schema | 50+ |

---

## 📡 API Endpoints

```bash
# Create sale (most important)
POST /api/v1/inventory/sales
{
  "items": [{"variantId": 1, "quantity": 2}],
  "paymentMethod": "cash",
  "discountAmount": 0
}

# List sales
GET /api/v1/inventory/sales?fiscal_year=2082/83

# Get invoice details
GET /api/v1/inventory/sales/1

# Process return
POST /api/v1/inventory/sales/1/return
{"reason": "Customer changed mind"}
```

---

## 💰 Tax Calculations

```
Subtotal = sum of (price × qty)
Taxable = Subtotal - Discount
VAT = Taxable × 13%
Grand Total = Taxable + VAT

Example:
Subtotal:    Rs 5000
Discount:    Rs 500
Taxable:     Rs 4500
VAT (13%):   Rs 585
Grand Total: Rs 5085
```

---

## 📧 Bill Number Format

```
FY = Nepali Fiscal Year (2082/83, 2083/84, etc.)
Bill# = FY-001, FY-002, FY-003 (increments per FY)

Full Format: 2082/83-001
│     │     └─ Sequential number
│     └────── End year (last 2 digits)
└──────────── Start year
```

---

## ⚠️ Common Issues & Fixes

| Issue | Cause | Fix |
|-------|-------|-----|
| "Variant not found" | Barcode doesn't exist | Assign barcode to variant |
| 401 Unauthorized | No JWT token | Re-login and check token |
| Stock not deducting | Migration not run | Run `php artisan migrate` |
| Wrong VAT | Timezone not set | Set to `Asia/Kathmandu` in config |
| No ledger entry | Bug in controller | Check logs: `tail storage/logs/laravel.log` |

---

## 🧪 Quick Test Commands

```bash
# Auth (get token)
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# Create sale (replace TOKEN)
curl -X POST http://localhost/api/v1/inventory/sales \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"items":[{"variantId":1,"quantity":2}],"paymentMethod":"cash"}'

# List today's sales
curl http://localhost/api/v1/inventory/sales \
  -H "Authorization: Bearer TOKEN"
```

---

## 📊 Monitoring Checklist

Daily:
- ☐ Check total sales count
- ☐ Verify stock accuracy against ledger
- ☐ Any failed transactions?

Weekly:  
- ☐ Review sales reports
- ☐ Check profit margins
- ☐ Stock ledger reconciliation

Monthly:
- ☐ Backup database
- ☐ Archive old sales
- ☐ Performance review

---

## 🆘 Emergency Contacts / Debugging

**If sale doesn't create:**
```bash
# Check migrations
php artisan migrate:status

# Check logs
tail -f storage/logs/laravel.log | grep -i error

# Test variant exists
php artisan tinker
>>> App\Models\ItemVariant::find(1)

# Test stock is sufficient
>>> App\Models\ItemVariant::find(1)->current_stock
```

**If barcode not scanning:**
```bash
# Verify barcode exists
>>> App\Models\ItemVariant::where('barcode', 'VAR...')->first()

# Check JS error
Press F12 → Console tab → Look for red errors
```

**If stock goes negative:**
```bash
# Check for partial updates
SELECT * FROM sale_items WHERE quantity > 
  (SELECT current_stock FROM item_variants);

# Restore from backup if critical
mysql < backup_YYYYMMDD_HHMMSS.sql
```

---

## 📚 Documentation Map

```
SALES_MODULE_DOCUMENTATION.md
├─ API Reference (requests/responses)
├─ Calculation Logic
├─ Testing Procedures
└─ Troubleshooting

IMPLEMENTATION_CHECKLIST.md
├─ Pre-deployment Verification
├─ Step-by-step Deployment
├─ Testing Procedures
└─ Maintenance & Monitoring

BARCODE_PRINTING_GUIDE.md
├─ Setup Instructions
├─ Label Templates
└─ Integration Tips

test-sales-api.sh
└─ Automated API Testing
```

---

## ⚡ Performance Tips

1. **Ensure indices are created:**
   ```sql
   SHOW INDEXES FROM sales;
   -- Should have: fiscal_year+bill_number
   ```

2. **Eager load relationships in high-traffic endpoints**
3. **Use pagination (default 50 per page)**
4. **Cache frequently accessed data (categories, items)**

---

## 🔐 Security Reminders

- ✅ Always send Authorization header with JWT token
- ✅ Use HTTPS in production
- ✅ Change default admin password
- ✅ Backup database regularly
- ✅ Monitor access logs
- ✅ Keep Laravel updated

---

## 📱 Mobile POS Tips

For best mobile POS experience:
1. Use device in landscape mode (wider cart display)
2. Connect to Bluetooth barcode scanner
3. Use thermal printer for fast receipts
4. Keep internet connection stable
5. Test barcode scanner before shift

---

## 🎯 Key Metrics to Track

```
Daily Revenue = SUM(grand_total) for status='completed'
Avg Transaction = Revenue / Count
Low Stock Items = WHERE current_stock <= reorder_level
Stock Accuracy = (Actual Count / System Count) × 100%
```

---

## 💡 Tips for Faster POS

1. Pre-scan high-selling items in order
2. Use Tab to jump between fields
3. Combine barcodes (scan sequentially)
4. Batch print invoices at end of day
5. Use keyboard only (faster than mouse clicks)

---

**Last Updated:** 2026-03-24  
**Version:** 1.0  
**Support:** Check documentation files for detailed help  

Print this card and keep near the POS terminal! 📋
