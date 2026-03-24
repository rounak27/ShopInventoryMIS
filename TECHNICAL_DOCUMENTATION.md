# ShopInventory Technical Documentation

## 1. Project Overview

ShopInventory is a Laravel 12 based inventory management system for clothing retail operations.

Core capabilities:
- Category management
- Item and variant management
- Supplier management
- Purchase (stock-in) workflow
- Stock adjustments (in, out, physical adjustment)
- Stock ledger and audit trail
- JWT-based API authentication
- jQuery + Blade single-shell dashboard UI


## 2. Technology Stack

Backend:
- PHP 8.2+
- Laravel Framework 12
- tymon/jwt-auth 2.3
- Eloquent ORM

Frontend:
- Blade templates
- jQuery
- Bootstrap 5.3
- Bootstrap Icons
- Toastr notifications
- Static JS modules in public/js

Build/Tooling:
- Vite 7
- Tailwind 4 listed in package dependencies (UI currently uses custom CSS + Bootstrap)


## 3. High-Level Architecture

- Web Layer:
  - Serves login and dashboard pages via WebController
- API Layer:
  - Versioned REST APIs under /api/v1
  - Inventory APIs under /api/v1/inventory
- Auth Layer:
  - JWT login/logout/register APIs
  - Protected APIs behind jwt.auth middleware
- Domain/Data Layer:
  - Eloquent models and controllers
  - MySQL-compatible migrations
- UI Layer:
  - Single dashboard blade with page sections toggled in JS
  - JS modules: app.js, items.js, stock.js, purchase-history.js, login.js


## 4. Application Entry Points

Web routes:
- GET / -> login page
- GET /login -> login page
- POST /login -> redirects to dashboard (web flow)
- GET /dashboard -> dashboard page
- GET /clear-cache -> clears Laravel caches

API base:
- /api/v1

API route groups:
- Auth:
  - POST /auth/login
  - POST /auth/register
  - POST /auth/logout (jwt.auth)
- Inventory (jwt.auth):
  - /inventory/categories (apiResource)
  - /inventory/items (apiResource)
  - /inventory/variants (apiResource)
  - /inventory/suppliers (apiResource)
  - GET /inventory/ledger
  - POST /inventory/stock/adjust
  - /inventory/purchases (index, store)


## 5. Authentication and Security

### 5.1 Guard Configuration
- Default guard is api
- api guard driver: jwt
- Provider: users

### 5.2 JWT Middleware Behavior
Protected API requests require Authorization: Bearer <token>.

JwtAuthMiddleware:
- Reads Authorization from:
  - request header
  - HTTP_AUTHORIZATION
  - REDIRECT_HTTP_AUTHORIZATION
- Returns 401 for missing/invalid/expired tokens
- Expired token response includes:
  - message: Token has expired
  - error: token_expired

### 5.3 Login Flow (Frontend)
- login.js posts credentials to /api/v1/auth/login
- On success, token is saved in localStorage
- API helper attaches Bearer token to protected calls
- If token expires, frontend clears token and redirects to /login?auth=expired


## 6. API Documentation

Response envelope pattern (most endpoints):
- success: boolean
- data: payload (list/object)
- message: optional
- meta: optional pagination metadata

### 6.1 Auth APIs

#### POST /api/v1/auth/register
Request body:
- name (string)
- username (string)
- email (string)
- password (string)

Response:
- success
- message

#### POST /api/v1/auth/login
Request body:
- username (required)
- password (required)

Success response:
- success
- token
- user: { id, name, username }

Failure:
- 401 Invalid username or password

#### POST /api/v1/auth/logout
Auth: Required

Response:
- success
- message


### 6.2 Category APIs
Base path: /api/v1/inventory/categories

Category DTO returned by API:
- id
- name
- description
- createdAt
- itemCount

Endpoints:
- GET /categories
- POST /categories
- PUT /categories/{id}
- DELETE /categories/{id}

Validation rules:
- name unique, max 100
- description max 500

Notes:
- Delete blocked with HTTP 409 if linked items exist


### 6.3 Item APIs
Base path: /api/v1/inventory/items

Item DTO returned by API:
- id
- name
- sku
- categoryId
- brand
- costPrice
- sellingPrice
- description
- category
- emoji (derived in API layer)
- variants: [{ id, size, color, stock }]

Endpoints:
- GET /items
  - Query params: search, category_id, page, per_page
- POST /items
- PUT /items/{id}
- DELETE /items/{id}

Validation highlights:
- sku unique
- categoryId exists:categories,id
- variants is array with size/color/stock

Transactional behavior:
- create/update wraps item + variants in DB transaction


### 6.4 Variant APIs
Base path: /api/v1/inventory/variants

Variant DTO returned by API:
- id
- itemId
- itemName
- sku
- size
- color
- variantKey (size-color)
- stock
- reorderLevel
- costPrice
- sellingPrice
- categoryId
- categoryName
- status: in_stock | low_stock | out_of_stock

Endpoints:
- GET /variants
  - Query params: item_id, category_id, status, search, per_page
- GET /variants/{id}
- POST /variants
- PUT /variants/{id}
- DELETE /variants/{id}

Rules and guards:
- Prevent duplicate (item_id + size + color)
- Delete blocked if ledger entries exist (409)


### 6.5 Supplier APIs
Base path: /api/v1/inventory/suppliers

Supplier DTO returned by API:
- id
- name
- contactPerson
- phone
- email
- address
- isActive
- purchaseCount
- createdAt

Endpoints:
- GET /suppliers
- GET /suppliers/{id}
- POST /suppliers
- PUT /suppliers/{id}
- DELETE /suppliers/{id}

Notes:
- DELETE performs soft deactivation by setting is_active = false


### 6.6 Purchase APIs
Base path: /api/v1/inventory/purchases

Purchase DTO returned by API:
- id
- poReference
- supplierName
- supplierId
- purchaseDate
- totalCost
- notes
- status
- createdBy
- lineCount
- items: [{ id, variantId, itemName, sku, size, color, variantKey, quantity, costPricePerUnit, totalCost }]

Endpoints:
- GET /purchases
  - Query params: search, date_from, date_to, page, per_page
- POST /purchases

Store request payload expected by controller:
- supplier (nullable string)
- supplierId (nullable id)
- date (required date)
- notes (nullable)
- items (array, min 1)
  - variantKey (required, exists item_variants.id)
  - qty (required int >= 1)
  - costPrice (required number >= 0)

Transactional behavior in POST /purchases:
1. Create purchase header
2. Create purchase item lines
3. Lock variant rows and increase stock
4. Insert stock ledger entries with action_type = purchase
5. Update purchase total_cost


### 6.7 Stock Adjustment API
Base path: /api/v1/inventory/stock

#### POST /stock/adjust
Supports operations:
- in
- out
- adjustment

Request fields:
- variantId (required)
- operation (required: in|out|adjustment)
- quantity (required for in/out)
- actualQty (required for adjustment)
- reason (required for out/adjustment)
- date (optional)
- note (optional)

Behavior:
- Runs in DB transaction
- lockForUpdate() on variant
- Writes stock_ledger snapshots (before/after)
- Returns updated variant + ledgerEntry payload


### 6.8 Ledger API
Base path: /api/v1/inventory/ledger

#### GET /ledger
Query params:
- search
- type
- item_id
- date_from
- date_to
- per_page

Ledger DTO returned by API:
- id
- date
- itemId
- variantKey
- type
- qty
- ref
- user
- note
- stockBefore
- stockAfter
- itemName
- sku
- variantSize
- variantColor


## 7. Database Schema

The schema below reflects migration files currently present.

### 7.1 users
- id (PK)
- name
- username (unique, added later migration)
- email (unique)
- email_verified_at
- password
- remember_token
- timestamps

### 7.2 categories
- id (PK)
- name (unique)
- description (nullable)
- timestamps

### 7.3 items
- id (PK)
- category_id (FK -> categories.id, cascadeOnDelete)
- name
- sku (unique)
- brand (nullable)
- cost_price decimal(10,2)
- selling_price decimal(10,2)
- description (nullable)
- image_path (nullable)
- is_active boolean default true
- timestamps

### 7.4 item_variants
- id (PK)
- item_id (FK -> items.id, cascadeOnDelete)
- size
- color
- current_stock int default 0
- reorder_level int default 0
- barcode unique nullable
- is_active boolean default true
- timestamps

### 7.5 suppliers
- id (PK)
- name
- contact_person
- phone
- email
- address
- is_active boolean default true
- timestamps

### 7.6 purchases
- id (PK)
- supplier_id (FK -> suppliers.id, nullable after later migration)
- supplier_name string(150) nullable (added later migration)
- created_by (FK -> users.id)
- po_reference unique
- purchase_date date
- total_cost decimal(12,2)
- notes text nullable
- status enum(draft, confirmed, received)
- timestamps

### 7.7 purchase_items
- id (PK)
- purchase_id (FK -> purchases.id, cascadeOnDelete)
- variant_id (FK -> item_variants.id)
- quantity int
- cost_price_per_unit decimal(10,2)
- total_cost decimal(12,2)
- composite index: (purchase_id, variant_id)
- no timestamps

### 7.8 sales
- id (PK)
- invoice_no unique
- created_by (FK -> users.id)
- total_amount decimal(12,2)
- discount decimal(10,2)
- tax decimal(10,2)
- payment_method
- sale_date datetime
- timestamps

### 7.9 sale_items
- id (PK)
- sale_id (FK -> sales.id, cascadeOnDelete)
- variant_id (FK -> item_variants.id)
- quantity int
- price_per_unit decimal(10,2)
- total_price decimal(12,2)
- composite index: (sale_id, variant_id)
- no timestamps

### 7.10 stock_ledgers
- id (PK)
- variant_id (FK -> item_variants.id)
- user_id (FK -> users.id)
- purchase_item_id (nullable FK -> purchase_items.id, nullOnDelete)
- action_type enum(purchase, sale, adjustment, return, damage)
- quantity_change int
- stock_before int
- stock_after int
- reference_no nullable
- notes nullable
- transaction_date date
- timestamps
- indexes: variant_id, transaction_date


## 8. Entity Relationships

Primary relationships:
- Category 1 -> N Items
- Item 1 -> N ItemVariants
- Supplier 1 -> N Purchases
- Purchase 1 -> N PurchaseItems
- ItemVariant 1 -> N PurchaseItems
- ItemVariant 1 -> N StockLedgers
- User 1 -> N Purchases (created_by)
- User 1 -> N StockLedgers (user_id)
- Sale 1 -> N SaleItems
- ItemVariant 1 -> N SaleItems


## 9. UI Components and Frontend Modules

### 9.1 Blade Views
- login.blade.php
  - login form
  - PWA install button
- layout.blade.php
  - app shell, sidebar, topbar, footer
  - script loading and module init
- dashboard.blade.php
  - all dashboard pages and modals in one template
  - pages toggled by showPage() and JS events

### 9.2 Main UI Pages
- Dashboard overview
- Item Management
- Category Management
- Current Stock
- Purchase / Stock In
- Stock History / Ledger

### 9.3 Key UI Components
- Sidebar navigation with low-stock badge
- Topbar with global search and PWA install button
- Stats cards (items, variants, stock, low stock, stock value)
- Data tables with pagination controls
- Form modals:
  - Item Add/Edit modal
  - Category modal
  - Purchase modal
  - Stock in/out/adjust modal(s)
  - Logout confirmation modal

### 9.4 JavaScript Modules
- app.js
  - global Config, Store, API wrapper, utilities
  - dashboard statistics refresh logic
- items.js
  - ItemMgr (items CRUD UI)
- stock.js
  - CatMgr and StockMgr
- purchase-history.js
  - PurchaseMgr and HistoryMgr
- login.js
  - auth/login page behavior
- apicall.js
  - alternate API helper (partially duplicated logic)

### 9.5 Frontend Data Flow
1. User action triggers JS module method
2. Module calls API helper (AJAX)
3. API returns normalized JSON payload
4. Store updates in-memory state
5. Module re-renders table/cards


## 10. Request/Response Pagination Contract

Common paginated response shape:
- success: true
- data: array
- meta:
  - total
  - currentPage
  - lastPage
  - perPage (present on some endpoints)

Frontend pagination utilities consume meta.currentPage and meta.lastPage.


## 11. Operational Workflows

### 11.1 Login
- Submit username/password
- Receive JWT token
- Save token in localStorage
- Redirect to dashboard

### 11.2 Add Item
- Submit item + variants
- Backend creates item and variant rows in transaction
- UI refreshes items/stock/statistics

### 11.3 Create Purchase
- Submit purchase header + lines
- Backend locks variants and increments stock
- Ledger rows inserted per line
- UI refreshes purchase list, stock view, ledger, stats

### 11.4 Stock Adjustment
- Choose operation (in/out/adjustment)
- Backend validates business rules (including no negative stock)
- Variant stock updated + ledger snapshot recorded


## 12. Notable Design/Implementation Notes

- API DTO naming intentionally differs from DB column naming to match frontend expectations:
  - categoryId vs category_id
  - costPrice vs cost_price
  - current_stock mapped as stock
- Emoji per item category is derived in backend response, not stored in DB.
- Supplier delete is logical deactivation, not hard delete.
- Stock changes are expected to be auditable through stock_ledgers.


## 13. Known Gaps and Improvement Targets

- Sales domain tables exist, but sales API/controller implementation is not currently present.
- Some frontend modules contain mixed old/new logic and commented legacy code.
- Supplier model currently has no explicit fillable/relationship declarations.
- There are two API helper implementations (app.js and apicall.js), which can be consolidated.
- FormRequest classes and API Resource classes are not yet used; validation/transforming is done in controllers.


## 14. Suggested API Standardization (Recommended)

To improve maintainability and external integration:
- Standardize request and response naming across all endpoints.
- Introduce consistent error envelope:
  - success: false
  - message
  - errors (field-level map)
- Move validation to FormRequest classes.
- Move DTO shaping to API Resource classes.
- Add OpenAPI spec generation for contract sharing.


## 15. File Map for Core Technical Areas

Backend:
- routes/api.php
- app/Http/Controllers/Api/AuthController.php
- app/Http/Controllers/Api/Inventory/*.php
- app/Http/Middleware/JwtAuthMiddleware.php
- app/Models/*.php
- database/migrations/*.php

Frontend:
- resources/views/layout.blade.php
- resources/views/dashboard.blade.php
- resources/views/login.blade.php
- public/js/app.js
- public/js/items.js
- public/js/stock.js
- public/js/purchase-history.js
- public/js/login.js


## 16. Run and Environment Notes

Typical local run steps:
1. composer install
2. copy .env and configure DB
3. php artisan key:generate
4. php artisan migrate
5. npm install
6. npm run dev
7. php artisan serve

JWT setup:
- Ensure JWT_SECRET is configured (or run php artisan jwt:secret)
- Use Authorization Bearer token on protected endpoints


## 17. Future Documentation Extensions

For production-grade docs, add:
- OpenAPI/Swagger JSON
- Sequence diagrams for purchase and stock adjustment transactions
- RBAC matrix (roles/permissions)
- Deployment architecture and monitoring setup
- Test coverage map by module
