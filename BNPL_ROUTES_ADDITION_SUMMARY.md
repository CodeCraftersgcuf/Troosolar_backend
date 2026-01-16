# BNPL Routes Addition Summary

## Overview
Added comprehensive routes and endpoints for users to access their BNPL order details and repayment information.

## New Routes Added

### 1. BNPL Orders Routes
```http
GET    /api/bnpl/orders
       - List all user's BNPL orders
       - Includes loan summary (total installments, paid, pending)
       - Supports filtering by status
       - Returns paginated results

GET    /api/bnpl/orders/{order_id}
       - Get complete BNPL order details
       - Includes:
         * Order information (items, total, status)
         * Linked loan application details
         * Complete repayment schedule
         * Repayment summary (counts and amounts)
         * Repayment history
         * Loan calculation details
```

### 2. Repayment Schedule Route
```http
GET    /api/bnpl/applications/{application_id}/repayment-schedule
       - Get repayment schedule for a specific BNPL application
       - Includes all installments with:
         * Payment dates
         * Amounts
         * Status (pending/paid/overdue)
         * Days until due
         * Transaction details (if paid)
       - Includes summary statistics
```

## Implementation Details

### Controller Methods Added
1. **BNPLController::getOrders()** - Lists all BNPL orders for authenticated user
2. **BNPLController::getOrderDetails()** - Returns detailed BNPL order with full repayment info
3. **BNPLController::getRepaymentSchedule()** - Returns repayment schedule for application
4. **BNPLController::formatBnplOrder()** - Helper method to format order data

### Data Returned

#### BNPL Order Details Include:
- Order basic info (id, order_number, status, total_price)
- Order items (products/bundles)
- Delivery address
- Loan application details (if linked)
- Complete repayment schedule with:
  - Installment number
  - Amount
  - Payment date
  - Status (pending/paid)
  - Overdue status
  - Transaction details (if paid)
- Repayment summary:
  - Total installments count
  - Paid installments count
  - Pending installments count
  - Overdue installments count
  - Total amount
  - Paid amount
  - Pending amount
- Repayment history (all payment records)
- Loan calculation details (amount, duration, interest rate, down payment)

#### Repayment Schedule Includes:
- All installments ordered by payment date
- Each installment with:
  - ID and installment number
  - Amount and payment date
  - Status and paid_at timestamp
  - Is_overdue flag
  - Days_until_due calculation
  - Transaction details (if paid)
- Summary statistics (same as order details)

## Files Modified

1. **app/Http/Controllers/Api/Website/BNPLController.php**
   - Added 3 new methods
   - Added helper method for formatting
   - Added imports for Order, LoanInstallment, LoanRepayment models

2. **routes/api.php**
   - Added 3 new routes under BNPL section

3. **BACKEND_REFERENCE_GUIDE.md**
   - Updated BNPL section with new routes
   - Added BNPL Orders & Repayment Management section
   - Updated BNPL Complete Flow to include order viewing and payment steps

4. **FRONTEND_USER_ROUTES_GUIDE.md** (NEW)
   - Created comprehensive frontend integration guide
   - Includes all user-facing routes
   - Detailed request/response formats
   - Authentication requirements
   - Error handling

## Integration Points

### Relationships Used:
- `Order` → `monoCalculation` (via `mono_calculation_id`)
- `MonoLoanCalculation` → `loanInstallments` (hasMany)
- `MonoLoanCalculation` → `loanRepayments` (hasMany)
- `LoanApplication` → `mono` (hasOne MonoLoanCalculation)
- `LoanInstallment` → `transaction` (belongsTo)

### Key Models:
- `Order` - BNPL orders (order_type = 'bnpl')
- `LoanApplication` - BNPL applications
- `MonoLoanCalculation` - Offered loan terms
- `LoanInstallment` - Monthly payment schedule
- `LoanRepayment` - Payment records
- `Transaction` - Payment transactions

## Testing Recommendations

1. **Test BNPL Order Listing:**
   - Create a BNPL order
   - Call `GET /api/bnpl/orders`
   - Verify order appears with loan summary

2. **Test Order Details:**
   - Call `GET /api/bnpl/orders/{order_id}`
   - Verify all repayment details are included
   - Check repayment schedule is complete

3. **Test Repayment Schedule:**
   - Call `GET /api/bnpl/applications/{application_id}/repayment-schedule`
   - Verify installments are ordered correctly
   - Check overdue calculations

4. **Test Installment Payment:**
   - Use existing `POST /api/installments/{id}/pay`
   - Verify payment updates order details

## Frontend Integration

The frontend can now:
1. Display all user's BNPL orders
2. Show detailed order information with repayment schedule
3. Display upcoming payments and overdue installments
4. Show payment history
5. Allow users to pay installments directly

## Next Steps

1. Frontend team should review `FRONTEND_USER_ROUTES_GUIDE.md`
2. Test all new endpoints with real data
3. Update frontend components to use new routes
4. Consider adding filters/sorting options if needed

---

**Date:** 2024-12-27  
**Status:** ✅ Complete and Ready for Testing

