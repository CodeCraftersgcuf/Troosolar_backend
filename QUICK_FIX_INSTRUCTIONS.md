# Quick Fix Instructions - Missing order_type Column

## Problem
The `orders` table is missing the `order_type` column, causing a 500 error when creating orders.

## Solution Options

### Option 1: Run Migrations (Recommended)

SSH into your production server and run:

```bash
cd /path/to/your/project
php artisan migrate
```

This will run all pending migrations, including the one that adds `order_type`.

### Option 2: Run SQL Script Directly

If migrations fail or you need a quick fix, run the SQL script:

1. Connect to your MySQL database
2. Run the SQL script: `fix_orders_table.sql`

Or run this SQL command directly:

```sql
ALTER TABLE `orders` 
ADD COLUMN `order_type` VARCHAR(50) DEFAULT 'buy_now' NULL 
COMMENT 'buy_now, bnpl, audit_only';
```

### Option 3: Use the Safe Migration

A new migration file has been created: `2025_11_27_120000_fix_orders_table_columns.php`

This migration safely checks if columns exist before adding them. Run:

```bash
php artisan migrate
```

## Verification

After running the migration or SQL, verify the column exists:

```sql
DESCRIBE orders;
```

Or check specific column:

```sql
SHOW COLUMNS FROM orders LIKE 'order_type';
```

## Additional Columns That May Be Missing

If the migration hasn't been run, these columns may also be missing:
- `material_cost`
- `delivery_fee`
- `inspection_fee`
- `insurance_fee`
- `installation_price`

The safe migration or SQL script will add all of these if they're missing.

## After Fix

Once the column is added, the checkout endpoint should work correctly. Test with:

```bash
POST /api/orders/checkout
```

---

**Note:** Always backup your database before running migrations or SQL scripts on production!

