# 📋 Sales Module - Implementation Checklist & Deployment Guide

## Pre-Deployment Verification

Before deploying to production, verify the following:

### Database & Models
- [ ] `app/Models/Sale.php` has all fillable fields and relationships
- [ ] `app/Models/SaleItem.php` includes cost_price, profit, discount_amount
- [ ] Migrations are created in `database/migrations/`
  - [ ] `2026_03_24_000001_update_sales_table_ird.php`
  - [ ] `2026_03_24_000002_update_sale_items_table.php`

### Backend Implementation
- [ ] Helpers created:
  - [ ] `app/Helpers/FiscalYearHelper.php`
  - [ ] `app/Helpers/BarcodeHelper.php`
- [ ] SalesController created: `app/Http/Controllers/Api/Inventory/SalesController.php`
- [ ] Routes added to `routes/api.php`:
  ```php
  Route::post('sales', [SalesController::class, 'store']);
  Route::get('sales', [SalesController::class, 'index']);
  Route::get('sales/{id}', [SalesController::class, 'show']);
  Route::post('sales/{id}/return', [SalesController::class, 'return']);
  ```

### Frontend Implementation
- [ ] Sales/POS page added to `resources/views/dashboard.blade.php`
- [ ] Sidebar menu includes "Sales / POS" link
- [ ] `public/js/sales.js` created with SalesMgr module
- [ ] `public/js/apicall.js` updated to include Authorization header in POST
- [ ] Script loaded in `resources/views/layout.blade.php`

### Configuration
- [ ] Timezone set to `Asia/Kathmandu` in `config/app.php`:
  ```php
  'timezone' => 'Asia/Kathmandu',
  ```
- [ ] JWT authentication configured and working
- [ ] Database connection verified

---

## 🚀 Deployment Steps

### Step 1: Backup Current Database
```bash
# Create backup
mysqldump -u root -p shop_inventory > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Copy Files to Server
```bash
# Pull latest code
git pull origin main

# Or copy files manually
scp -r app/ user@server:/path/to/app/
scp routes/api.php user@server:/path/to/routes/
scp resources/views/dashboard.blade.php user@server:/path/to/resources/views/
scp public/js/sales.js user@server:/path/to/public/js/
```

### Step 3: Install Dependencies
```bash
composer install
npm install
npm run build  # If using Vite
```

### Step 4: Run Migrations
```bash
php artisan migrate --force

# Verify migration status
php artisan migrate:status
```

### Step 5: Test API Endpoints
```bash
# Run the test script
bash test-sales-api.sh

# Or test manually with curl
curl -X GET "http://localhost/api/v1/inventory/sales" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Step 6: Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:cache
php artisan config:cache
```

---

## 🧪 Testing Procedures

### Unit Tests (if using Laravel Tests)
```php
// tests/Feature/SalesControllerTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Sale;
use App\Models\ItemVariant;
use App\Models\User;

class SalesControllerTest extends TestCase
{
    protected $user;
    protected $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        // Create test variant with barcode
        $this->variant = ItemVariant::factory()
            ->create(['barcode' => 'TEST001']);
    }

    public function test_can_create_sale()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/inventory/sales', [
                'customerName' => 'Test Customer',
                'paymentMethod' => 'cash',
                'items' => [
                    [
                        'variantId' => $this->variant->id,
                        'quantity' => 2,
                    ]
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sale.status', 'completed');
    }

    public function test_prevents_overselling()
    {
        $this->variant->update(['current_stock' => 1]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/inventory/sales', [
                'paymentMethod' => 'cash',
                'items' => [
                    [
                        'variantId' => $this->variant->id,
                        'quantity' => 2, // More than stock
                    ]
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}

// Run tests
php artisan test
```

### Manual Testing Checklist

#### UI Testing
- [ ] Dashboard loads without console errors
- [ ] Sales/POS page accessible from menu
- [ ] Barcode input has autofocus
- [ ] Typing a valid barcode adds to cart
- [ ] Invalid barcode shows "not found" toast
- [ ] Cart displays item correctly
- [ ] Qty change updates summary in real-time
- [ ] Remove button deletes from cart
- [ ] Clear cart button empties the cart
- [ ] Discount percentage updates grand total
- [ ] Payment method selector works

#### Functional Testing
- [ ] Sale creation (barcode scan method)
  - [ ] Correct stock deduction
  - [ ] Ledger entry created
  - [ ] Bill number generated correctly
  - [ ] VAT calculated as 13%
  - [ ] Invoice displays correctly

- [ ] Sale listing & filtering
  - [ ] Filter by fiscal year works
  - [ ] Filter by date range works
  - [ ] Filter by customer name works
  - [ ] Pagination shows correct results

- [ ] Sale return
  - [ ] Stock restored after return
  - [ ] Ledger entry marked as "return"
  - [ ] Sale status changes to "returned"

#### Data Integrity
- [ ] Stock never goes negative
- [ ] Ledger entries match stock changes
- [ ] Bill numbers are sequential per FY
- [ ] Profit calculated correctly (selling_price - cost_price) × qty
- [ ] Customer data saved if provided

---

## 🔍 Troubleshooting Guide

### Issue: "Call to undefined method..." in SalesController

**Cause:** Missing import statement  
**Fix:** Ensure these are at top of SalesController:
```php
use App\Helpers\FiscalYearHelper;
use App\Helpers\BarcodeHelper;
use App\Models\StockLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
```

### Issue: 401 Unauthorized on POST /inventory/sales

**Cause:** Authorization header not being sent  
**Fix:** Verify `apicall.js` includes Authorization header in POST:
```javascript
headers: { 
  'Authorization': 'Bearer ' + API.getToken(),
  ...
}
```

### Issue: Stock not deducting after sale

**Cause:** Migration didn't run or stock field not updating  
**Fix:**
```bash
# Check column exists
php artisan tinker
>>> \App\Models\ItemVariant::first()->current_stock

# Re-run migrations if needed
php artisan migrate:refresh --path=database/migrations/2026_03_24*
```

### Issue: "Fiscal year not recognized" error

**Cause:** Timezone not set to Asia/Kathmandu  
**Fix:** Update `config/app.php`:
```php
'timezone' => 'Asia/Kathmandu',
```

### Issue: Duplicate bill numbers generated

**Cause:** Race condition in concurrent requests  
**Fix:** Ensure DB::transaction() wraps all bill generation:
```php
DB::transaction(function () {
    $billNo = FiscalYearHelper::getNextBillNumber($fiscalYear);
    // ... create sale
}, 3); // Retry 3 times on deadlock
```

### Issue: VAT calculation doesn't add up

**Cause:** Order of calculation  
**Fix:** Ensure:
```
Taxable = Subtotal - Discount
VAT = Taxable × 0.13
Grand Total = Taxable + VAT
```

---

## 📊 Database Schema Reference

### `sales` table
```sql
SELECT * FROM sales LIMIT 1;

-- Key columns:
-- bill_number (unique) | 2082/83-001
-- fiscal_year (indexed) | 2082/83
-- customer_name varchar
-- customer_pan varchar
-- sub_total decimal(12,2)
-- discount_amount decimal(10,2)
-- taxable_amount decimal(12,2)
-- vat decimal(10,2)
-- grand_total decimal(12,2)
-- payment_method enum
-- status enum
-- created_by (FK to users)
-- created_at, updated_at
```

### `sale_items` table  
```sql
SELECT * FROM sale_items LIMIT 1;

-- Key columns:
-- sale_id (FK)
-- variant_id (FK, indexed)
-- quantity int
-- price_per_unit decimal(10,2)
-- cost_price decimal(10,2)
-- profit decimal(10,2)
-- discount_amount decimal(10,2)
-- total_price decimal(12,2)
```

### `stock_ledgers` table
```sql
SELECT * FROM stock_ledgers 
WHERE action_type = 'sale' LIMIT 5;

-- Key columns:
-- variant_id (FK, indexed)
-- user_id (FK)
-- action_type (sale, purchase, return, adjustment)
-- quantity_change int (+ or -)
-- stock_before int
-- stock_after int
-- reference_no varchar (bill number)
-- transaction_date timestamp
```

---

## 🎯 Performance Optimization

### Database Indices
```sql
-- Verify indices exist:
SHOW INDEXES FROM sales;
SHOW INDEXES FROM sale_items;
SHOW INDEXES FROM stock_ledgers;

-- Expected indices:
-- sales: PRIMARY, fiscal_year+bill_number, created_by
-- sale_items: PRIMARY, sale_id, variant_id
-- stock_ledgers: PRIMARY, variant_id, action_type
```

### Cache Config
Add to `.env`:
```
CACHE_STORE=redis
QUEUE_DRIVER=redis
```

### Query Optimization
Ensure eager loading in SalesController:
```php
Sale::with(['items.variant.item', 'creator'])
    ->orderByDesc('id')
    ->paginate(50);
```

---

## 📱 Mobile Responsive Testing

Test on actual devices:
- [ ] iPhone 12 (375px width)
- [ ] iPad (768px width)
- [ ] Android phone (360px width)
- [ ] Desktop (1920px width)

Key touchpoints:
- [ ] Barcode input keyboard friendly
- [ ] Cart scrolls smoothly on mobile
- [ ] Summary sidebar responsive
- [ ] Buttons large enough to tap (48px minimum)
- [ ] Invoice prints correctly on mobile

---

## 🔐 Security Audit

Before going live:
- [ ] All API endpoints require `jwt.auth` middleware
- [ ] SQL injection prevented (using Eloquent ORM)
- [ ] CSRF token in forms
- [ ] Input validation on all endpoints
- [ ] User data properly sanitized
- [ ] No sensitive data in logs
- [ ] Rate limiting configured if needed
- [ ] SSL/HTTPS enforced in production

---

## 📞 Support & Maintenance

### Monitoring
Monitor these metrics daily:
- Total sales count
- Average transaction value
- Stock accuracy (compare ledger to physical)
- Failed sale transactions
- API response times

### Regular Maintenance
- Daily: Review failed transactions and errors
- Weekly: Verify stock ledger accuracy
- Monthly: Generate sales reports
- Quarterly: Review and optimize queries

### Log Files
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check specific errors
grep -i "error" storage/logs/laravel.log | tail -20

# Clear old logs
find storage/logs -name "*.log" -mtime +30 -delete
```

---

## Version History

| Version | Date       | Changes |
|---------|-----------|---------|
| 1.0     | 2026-03-24| Initial release with core POS, barcode scanning, and IRD compliance |

---

**Status: ✅ Production Ready**  
**Last Tested: 2026-03-24**  
**Maintained By: Your Team**
