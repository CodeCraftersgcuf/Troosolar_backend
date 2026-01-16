-- Fix Existing BNPL Orders
-- This script updates orders that have mono_calculation_id but don't have order_type='bnpl'

-- Step 1: Check current state
SELECT 
    COUNT(*) as total_orders,
    COUNT(CASE WHEN order_type = 'bnpl' THEN 1 END) as bnpl_orders,
    COUNT(CASE WHEN mono_calculation_id IS NOT NULL AND (order_type IS NULL OR order_type != 'bnpl') THEN 1 END) as needs_fix
FROM orders;

-- Step 2: Update orders with mono_calculation_id to have order_type='bnpl'
-- Only update if order_type is NULL or empty (not if it's already set to something else)
UPDATE orders 
SET order_type = 'bnpl' 
WHERE mono_calculation_id IS NOT NULL 
  AND (order_type IS NULL OR order_type = '' OR order_type = 'buy_now');

-- Step 3: Verify the update
SELECT 
    id,
    order_type,
    mono_calculation_id,
    order_status,
    payment_status,
    total_price,
    created_at
FROM orders 
WHERE mono_calculation_id IS NOT NULL
ORDER BY created_at DESC
LIMIT 20;

-- Step 4: Check for any remaining issues
SELECT 
    id,
    order_type,
    mono_calculation_id,
    CASE 
        WHEN mono_calculation_id IS NOT NULL AND order_type != 'bnpl' THEN 'NEEDS FIX'
        WHEN mono_calculation_id IS NOT NULL AND order_type = 'bnpl' THEN 'OK'
        ELSE 'N/A'
    END as status
FROM orders 
WHERE mono_calculation_id IS NOT NULL
ORDER BY created_at DESC;

