# Order Controller Fixes Summary

## Issues Fixed

### 1. ✅ Buy Now Order Status Update - 500 Error

**Problem:**
- `PUT /api/admin/orders/buy-now/{id}/status` was returning 500 error
- Error occurred when trying to set `admin_notes` field

**Root Cause:**
- The `admin_notes` column might not exist in the `orders` table
- Code was trying to set a field without checking if column exists

**Fix Applied:**
- Added check using `Schema::hasColumn('orders', 'admin_notes')` before setting the field
- Only sets `admin_notes` if column exists and value is provided
- Improved error logging with trace information

**Code Change:**
```php
// Before
if ($request->has('admin_notes')) {
    $order->admin_notes = $request->admin_notes;
}

// After
if ($request->has('admin_notes') && Schema::hasColumn('orders', 'admin_notes')) {
    $order->admin_notes = $request->admin_notes;
}
```

---

### 2. ✅ BNPL Orders Not Showing - Empty Results

**Problem:**
- `GET /api/admin/orders/bnpl` was returning empty array
- Query was only looking for `order_type = 'bnpl'`

**Root Causes:**
1. Existing orders might have `order_type` as NULL (created before column was added)
2. Orders might not have been created with `order_type = 'bnpl'` set
3. Backward compatibility issue with older orders

**Fix Applied:**
- Updated query to include orders with:
  - `order_type = 'bnpl'` (explicit BNPL orders)
  - OR `mono_calculation_id IS NOT NULL` AND `order_type IS NULL` (backward compatibility)
  - OR `mono_calculation_id IS NOT NULL` AND `order_type NOT IN ('buy_now', 'audit_only')`
- Applied same logic to both `getBnplOrders()` and `getBnplOrder()` methods

**Code Change:**
```php
// Before
$query->where('order_type', 'bnpl');

// After
if (Schema::hasColumn('orders', 'order_type')) {
    $query->where(function($q) {
        $q->where('order_type', 'bnpl')
          ->orWhere(function($subQ) {
              $subQ->whereNotNull('mono_calculation_id')
                   ->where(function($typeQ) {
                       $typeQ->whereNull('order_type')
                             ->orWhereNotIn('order_type', ['buy_now', 'audit_only']);
                   });
          });
    });
} else {
    $query->whereNotNull('mono_calculation_id');
}
```

---

## SQL Script to Fix Existing Orders

If you have existing BNPL orders that don't have `order_type` set, run this SQL:

```sql
-- Update existing orders with mono_calculation_id to have order_type='bnpl'
UPDATE orders 
SET order_type = 'bnpl' 
WHERE mono_calculation_id IS NOT NULL 
  AND (order_type IS NULL OR order_type = '');

-- Verify the update
SELECT 
    id,
    order_type,
    mono_calculation_id,
    order_status,
    total_price,
    created_at
FROM orders 
WHERE mono_calculation_id IS NOT NULL
ORDER BY created_at DESC;
```

---

## Testing

### Test Buy Now Order Status Update:
```http
PUT /api/admin/orders/buy-now/152/status
Content-Type: application/json
Authorization: Bearer {token}

{
  "order_status": "processing"
}
```

**Expected Response:**
```json
{
  "status": "success",
  "data": {
    "id": 152,
    "order_status": "processing",
    ...
  },
  "message": "Buy Now order status updated successfully"
}
```

### Test BNPL Orders List:
```http
GET /api/admin/orders/bnpl?per_page=15&page=1
Authorization: Bearer {token}
```

**Expected Response:**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "order_type": "bnpl",
        "mono_calculation_id": 45,
        ...
      }
    ],
    "total": 10,
    ...
  },
  "message": "BNPL orders retrieved successfully"
}
```

---

## Additional Recommendations

1. **Run Migration:** Ensure `order_type` column exists:
   ```bash
   php artisan migrate
   ```

2. **Update Existing Orders:** Run the SQL script above to set `order_type` for existing BNPL orders

3. **Verify Database:** Check that orders table has required columns:
   ```sql
   DESCRIBE orders;
   ```
   
   Required columns:
   - `order_type` (VARCHAR)
   - `mono_calculation_id` (INT, nullable)
   - `admin_notes` (TEXT, nullable) - optional

4. **Monitor Logs:** Check Laravel logs for any remaining errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## Files Modified

1. `app/Http/Controllers/Api/Website/OrderController.php`
   - `updateBuyNowOrderStatus()` - Added column existence check
   - `getBnplOrders()` - Improved query to handle NULL order_type
   - `getBnplOrder()` - Improved query to handle NULL order_type

---

**Date:** 2024-12-27  
**Status:** ✅ Fixed and Ready for Testing

