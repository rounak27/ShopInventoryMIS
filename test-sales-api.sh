#!/bin/bash

# ════════════════════════════════════════════════════════════════════════════
# SALES API — TESTING & VALIDATION COMMANDS
# ════════════════════════════════════════════════════════════════════════════
# 
# Prerequisites:
# 1. Laravel app running on localhost
# 2. Database migrated: php artisan migrate
# 3. User account created (or use seed)
#
# Usage: bash test-sales-api.sh
# ════════════════════════════════════════════════════════════════════════════

BASE_URL="http://localhost"
API_BASE="$BASE_URL/api/v1"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}   SHOPINVENTORY — SALES API TESTING${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════════════${NC}\n"

# ── Step 1: Login ──────────────────────────────────────────────────────────
echo -e "${YELLOW}[1/4] AUTHENTICATING...${NC}"

LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "password"
  }')

TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  echo -e "${RED}✗ Authentication failed${NC}"
  echo "Response: $LOGIN_RESPONSE"
  exit 1
fi

echo -e "${GREEN}✓ Authenticated successfully${NC}"
echo -e "   Token: ${TOKEN:0:30}..."

# ── Step 2: Create a Sale ──────────────────────────────────────────────────
echo -e "\n${YELLOW}[2/4] CREATING A SALE...${NC}"

SALE_RESPONSE=$(curl -s -X POST "$API_BASE/inventory/sales" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customerName": "Test Customer",
    "customerPan": "5021234567PAN001",
    "paymentMethod": "cash",
    "discountAmount": 50,
    "items": [
      {
        "variantId": 1,
        "quantity": 2
      }
    ]
  }')

SALE_ID=$(echo "$SALE_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
BILL_NUMBER=$(echo "$SALE_RESPONSE" | grep -o '"billNumber":"[^"]*' | cut -d'"' -f4)

if [ -z "$SALE_ID" ]; then
  echo -e "${RED}✗ Sale creation failed${NC}"
  echo "Response: $SALE_RESPONSE"
  exit 1
fi

echo -e "${GREEN}✓ Sale created successfully${NC}"
echo -e "   Sale ID: $SALE_ID"
echo -e "   Bill Number: $BILL_NUMBER"

# ── Step 3: Retrieve Sale Details ──────────────────────────────────────────
echo -e "\n${YELLOW}[3/4] RETRIEVING SALE DETAILS...${NC}"

GET_RESPONSE=$(curl -s -X GET "$API_BASE/inventory/sales/$SALE_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

GRAND_TOTAL=$(echo "$GET_RESPONSE" | grep -o '"grandTotal":[0-9.]*' | head -1 | cut -d':' -f2)
STATUS=$(echo "$GET_RESPONSE" | grep -o '"status":"[^"]*' | cut -d'"' -f4)

if [ -z "$GRAND_TOTAL" ]; then
  echo -e "${RED}✗ Failed to retrieve sale${NC}"
  echo "Response: $GET_RESPONSE"
  exit 1
fi

echo -e "${GREEN}✓ Sale retrieved successfully${NC}"
echo -e "   Grand Total: Rs. $GRAND_TOTAL"
echo -e "   Status: $STATUS"

# ── Step 4: List Sales with Filters ────────────────────────────────────────
echo -e "\n${YELLOW}[4/4] LISTING SALES...${NC}"

LIST_RESPONSE=$(curl -s -X GET "$API_BASE/inventory/sales?per_page=5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")

TOTAL_SALES=$(echo "$LIST_RESPONSE" | grep -o '"total":[0-9]*' | head -1 | cut -d':' -f2)

if [ -z "$TOTAL_SALES" ]; then
  echo -e "${RED}✗ Failed to list sales${NC}"
  echo "Response: $LIST_RESPONSE"
  exit 1
fi

echo -e "${GREEN}✓ Sales list retrieved${NC}"
echo -e "   Total Sales: $TOTAL_SALES"

# ── Summary ────────────────────────────────────────────────────────────────
echo -e "\n${GREEN}════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}   ALL TESTS PASSED ✓${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════════════${NC}\n"

echo -e "Quick Summary:"
echo -e "  • Authentication: ✓"
echo -e "  • Sale Creation: ✓ (Bill: $BILL_NUMBER)"
echo -e "  • Sale Retrieval: ✓ (Total: Rs. $GRAND_TOTAL)"
echo -e "  • Sales Listing: ✓ (Total Records: $TOTAL_SALES)"

echo -e "\nNext Steps:"
echo -e "  1. Test barcode scanning in dashboard"
echo -e "  2. Verify stock ledger entries"
echo -e "  3. Test return functionality"
echo -e "  4. Print invoice"

# ────────────────────────────────────────────────────────────────────────────
# ADDITIONAL MANUAL TESTS
# ────────────────────────────────────────────────────────────────────────────

echo -e "\n${BLUE}ADDITIONAL TEST COMMANDS (run manually):${NC}\n"

echo -e "${YELLOW}Test: Return a sale${NC}"
echo "curl -X POST \"$API_BASE/inventory/sales/1/return\" \\"
echo "  -H \"Authorization: Bearer TOKEN\" \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{\"reason\":\"Customer changed mind\"}'"

echo -e "\n${YELLOW}Test: Filter sales by fiscal year${NC}"
echo "curl -X GET \"$API_BASE/inventory/sales?fiscal_year=2082/83\" \\"
echo "  -H \"Authorization: Bearer TOKEN\""

echo -e "\n${YELLOW}Test: Filter sales by date range${NC}"
echo "curl -X GET \"$API_BASE/inventory/sales?from_date=2026-03-20&to_date=2026-03-24\" \\"
echo "  -H \"Authorization: Bearer TOKEN\""

echo -e "\n${YELLOW}Test: Search sales by bill number${NC}"
echo "curl -X GET \"$API_BASE/inventory/sales?bill_number=2082/83-001\" \\"
echo "  -H \"Authorization: Bearer TOKEN\""

echo -e "\n${YELLOW}Test: Filter by payment method${NC}"
echo "curl -X GET \"$API_BASE/inventory/sales?status=completed\" \\"
echo "  -H \"Authorization: Bearer TOKEN\""
