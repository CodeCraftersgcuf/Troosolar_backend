# Frontend User Routes Guide - TrooSolar Backend
**Last Updated:** 2024-12-27  
**Purpose:** Complete reference for all user-facing API endpoints for frontend integration

---

## 📑 Table of Contents

1. [Authentication & User Management](#authentication--user-management)
2. [BNPL (Buy Now Pay Later) Routes](#bnpl-buy-now-pay-later-routes)
3. [BNPL Orders & Repayment Routes](#bnpl-orders--repayment-routes)
4. [Buy Now Flow Routes](#buy-now-flow-routes)
5. [Order Management Routes](#order-management-routes)
6. [Loan Management Routes](#loan-management-routes)
7. [Audit Request Routes](#audit-request-routes)
8. [Cart System Routes](#cart-system-routes)
9. [Wallet & Transactions Routes](#wallet--transactions-routes)
10. [Support & Communication Routes](#support--communication-routes)
11. [Configuration Routes](#configuration-routes)

---

## 🔐 Authentication & User Management

### Public Routes (No Auth Required)
```http
POST   /api/register
POST   /api/verify-otp/{user_id}
POST   /api/login
POST   /api/admin-login
POST   /api/forget-password
POST   /api/verify-reset-password-otp
POST   /api/reset-password
```

### Protected Routes (Auth Required)
```http
POST   /api/logout
POST   /api/update-user
GET    /api/send-otp
GET    /api/single-user/{user_id}
DELETE /api/delete-user/{user_id}
GET    /api/get-referral-details
```

---

## 💳 BNPL (Buy Now Pay Later) Routes

### Application Management
```http
GET    /api/bnpl/applications
       Query Params: status (optional), per_page (optional, default: 15)
       Returns: List of all user's BNPL applications with pagination

POST   /api/bnpl/apply
       Body: customer_type, product_category, loan_amount, repayment_duration, 
             credit_check_method, personal_details, property_details, 
             bank_statement (file), live_photo (file)
       Returns: Created loan application

GET    /api/bnpl/status/{application_id}
       Returns: Detailed application status with loan calculation and guarantor info

GET    /api/bnpl/applications/{application_id}/repayment-schedule
       Returns: Complete repayment schedule with installments, payment dates, 
                and summary statistics
```

### Guarantor Management
```http
POST   /api/bnpl/guarantor/invite
       Body: loan_application_id, full_name, email, phone, bvn, relationship
       Returns: Created guarantor record

POST   /api/bnpl/guarantor/upload
       Body: guarantor_id, signed_form (file)
       Returns: Updated guarantor with form path

POST   /api/bnpl/counteroffer/accept
       Body: application_id, minimum_deposit, minimum_tenor
       Returns: Updated application status
```

---

## 🛒 BNPL Orders & Repayment Routes

### BNPL Orders
```http
GET    /api/bnpl/orders
       Query Params: status (optional), per_page (optional, default: 15)
       Returns: List of all user's BNPL orders with loan summary

GET    /api/bnpl/orders/{order_id}
       Returns: Complete BNPL order details including:
                - Order information
                - Loan application details
                - Complete repayment schedule
                - Repayment summary (total, paid, pending, overdue)
                - Repayment history
                - Loan calculation details
```

### Loan Installments & Repayments
```http
GET    /api/installments/with-history
       Returns: Current month installments and history with overdue information

POST   /api/installments/{installmentId}/pay
       Body: method (wallet|bank|card|transfer), type (shop|loan, if wallet), 
             tx_id (required if not wallet), reference, title
       Returns: Updated installment with transaction details

GET    /api/show-loan-installment/{monoCalculationId}
       Returns: All installments for a specific loan calculation
```

---

## 🛍️ Buy Now Flow Routes

### Checkout & Order Creation
```http
POST   /api/orders/checkout
       Body: product_id OR bundle_id OR amount, customer_type, product_category,
             installer_choice, include_insurance, include_inspection,
             state_id, delivery_location_id, add_ons[], audit_type, etc.
       Returns: Invoice breakdown with order_id

GET    /api/orders/{id}/summary
       Returns: Order summary with items, appliances, backup time

GET    /api/orders/{id}/invoice-details
       Returns: Detailed invoice breakdown (inverter, panels, batteries, fees)

POST   /api/order/payment-confirmation
       Body: amount, orderId, txId, type (direct|audit|wallet)
       Returns: Payment confirmation with transaction details

POST   /api/order/pay-by-loan
       Body: order_id, amount
       Returns: Payment confirmation using loan wallet
```

---

## 📦 Order Management Routes

### General Order Routes
```http
GET    /api/orders
       Returns: List of all user's orders (or all orders if admin)

GET    /api/orders/{id}
       Returns: Single order details with items and delivery address

POST   /api/orders
       Body: delivery_address_id, payment_method, note
       Returns: Created order from cart

DELETE /api/orders/{id}
       Returns: Deleted order confirmation

GET    /api/orders/user/{userId}
       Returns: Orders for specific user (admin usage)
```

---

## 💰 Loan Management Routes

### Loan Calculation
```http
POST   /api/loan-calculation
       Body: loan_amount, repayment_duration, deposit_percentage, etc.
       Returns: Created loan calculation with status 'calculated'

POST   /api/loan-calculation-finalized/{id}
       Returns: Finalized loan calculation

GET    /api/loan-calculation-stauts
       Returns: Current loan calculation status

GET    /api/offered-loan-calculation
       Returns: Admin-offered loan calculation (status: 'offered')

POST   /api/loan-calculator-tool
       Body: loan_amount, repayment_duration, deposit_percentage
       Returns: Calculated loan terms (no database save)
```

### Loan Application & Documents
```http
POST   /api/loan-application/{monoLoanCalculationId}
       Body: Documents and application details
       Returns: Created loan application

GET    /api/all-loan-application
       Returns: List of all user's loan applications

GET    /api/single-loan-application/{id}
       Returns: Single loan application details

DELETE /api/delete-loan-application/{loanApplicationId}
       Returns: Deleted application confirmation

POST   /api/beneficiary-detail/{monoLoanCalculationId}
       Body: Beneficiary information
       Returns: Updated loan application

POST   /api/loan-details/{monoLoanCalculationId}
       Body: Loan details
       Returns: Updated loan application

GET    /api/single-document/{mono_loan_calculation_id}
       Returns: Document details

GET    /api/single-beneficiary/{mono_loan_calculation_id}
       Returns: Beneficiary details

GET    /api/single-loan-detail/{mono_loan_calculation_id}
       Returns: Loan detail information
```

### Loan History & Status
```http
POST   /api/loan-history/{loanApplicatioId}
       Returns: Loan history with repayments and distribution info

GET    /api/all-loan-status
       Returns: All loan statuses for user

GET    /api/loan-dashboard
       Returns: Loan dashboard summary
```

---

## 🏠 Audit Request Routes

### Audit Request Management
```http
POST   /api/audit/request
       Body: audit_type (home-office|commercial), customer_type, 
             property_details (required for home-office, optional for commercial)
       Returns: Created audit request (and order for home-office)

GET    /api/audit/request/{id}
       Returns: Single audit request status

GET    /api/audit/requests
       Returns: List of all user's audit requests
```

---

## 🛍️ Cart System Routes

### Cart Management
```http
GET    /api/cart
       Returns: User's cart with all items

POST   /api/cart
       Body: itemable_type (product|bundles), itemable_id, quantity, unit_price
       Returns: Added cart item

PUT    /api/cart/{id}
       Body: quantity
       Returns: Updated cart item

DELETE /api/cart/{id}
       Returns: Removed cart item

DELETE /api/cart
       Returns: Cleared cart confirmation

GET    /api/cart/checkout-summary
       Returns: Cart checkout summary with totals

GET    /api/cart/access/{token}
       Returns: Access cart via email token (public route)
```

---

## 💰 Wallet & Transactions Routes

### Wallet Management
```http
GET    /api/loan-wallet
       Returns: User's loan wallet balance and details

POST   /api/fund-wallet
       Body: amount, method, tx_id
       Returns: Updated wallet with transaction
```

### Transactions
```http
GET    /api/transactions
       Returns: List of all user's transactions

GET    /api/transactions/{id}
       Returns: Single transaction details

GET    /api/transactions/user/{userId}
       Returns: Transactions for specific user

GET    /api/single-trancastion
       Returns: Single transaction (current user)

POST   /api/withdraw
       Body: amount, method, account_details
       Returns: Created withdrawal request

GET    /api/withdraw/get
       Returns: User's withdrawal requests
```

---

## 💬 Support & Communication Routes

### Tickets
```http
GET    /api/website/tickets
       Returns: List of user's tickets

POST   /api/website/tickets
       Body: subject, message, priority, category
       Returns: Created ticket

GET    /api/website/tickets/{id}
       Returns: Single ticket with replies

PUT    /api/website/tickets/{id}
       Body: subject, message, status
       Returns: Updated ticket

DELETE /api/website/tickets/{id}
       Returns: Deleted ticket confirmation
```

### Notifications
```http
GET    /api/user-notifications
       Returns: User's notifications
```

---

## ⚙️ Configuration Routes

### Public Configuration
```http
GET    /api/config/customer-types
       Returns: Available customer types (residential, sme, commercial)

GET    /api/config/audit-types
       Returns: Available audit types (home-office, commercial)

GET    /api/config/states
       Returns: List of states with delivery/installation fees

GET    /api/config/loan-configuration
       Returns: Loan configuration (tenor options, interest rates, etc.)

GET    /api/config/add-ons
       Returns: Available add-ons

GET    /api/config/delivery-locations
       Returns: Delivery locations with fees
```

---

## 📅 Calendar & Scheduling Routes

### Calendar Slots
```http
GET    /api/calendar/slots
       Query Params: type (installation|audit), date (optional)
       Returns: Available calendar slots for booking
```

---

## 🔍 KYC Routes

### KYC Management
```http
POST   /api/kyc
       Body: Documents and KYC information
       Returns: Created KYC submission

GET    /api/kyc/status
       Returns: User's KYC status

POST   /api/kyc/{kyc}/replace-file
       Body: file, document_type
       Returns: Updated KYC with replaced file
```

---

## 📊 Response Format

All API responses follow this structure:

### Success Response
```json
{
  "status": "success",
  "data": {
    // Response data
  },
  "message": "Human-readable success message"
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Human-readable error message",
  "errors": {
    // Validation errors (if applicable)
  }
}
```

### Pagination Response
```json
{
  "status": "success",
  "data": {
    "data": [...],
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 75,
      "from": 1,
      "to": 15
    }
  },
  "message": "Data retrieved successfully"
}
```

---

## 🔑 Authentication

All protected routes require authentication via Laravel Sanctum.

### Headers Required
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Getting Token
After login via `/api/login`, the response includes:
```json
{
  "status": "success",
  "data": {
    "user": {...},
    "token": "1|xxxxxxxxxxxxx"
  }
}
```

Use this token in the `Authorization` header for subsequent requests.

---

## 📝 Important Notes

### BNPL Order Flow
1. User creates loan calculation → `POST /api/loan-calculation`
2. Admin offers loan → User calls `GET /api/offered-loan-calculation`
3. User applies for BNPL → `POST /api/bnpl/apply`
4. Admin approves → User invites guarantor → `POST /api/bnpl/guarantor/invite`
5. Order created → User can view via `GET /api/bnpl/orders`
6. View repayment schedule → `GET /api/bnpl/orders/{order_id}` or `GET /api/bnpl/applications/{application_id}/repayment-schedule`
7. Pay installments → `POST /api/installments/{installmentId}/pay`

### File Uploads
- **Bank Statement:** PDF, JPG, PNG (max 10MB)
- **Live Photo:** JPG, PNG (max 5MB)
- **Guarantor Form:** PDF, JPG, PNG (max 10MB)
- **Profile Picture:** Image (max 2MB)

Use `multipart/form-data` content type for file uploads.

### Date Formats
- API expects: `YYYY-MM-DD` for dates
- API returns: `YYYY-MM-DD` or `YYYY-MM-DD HH:MM:SS` for timestamps

### Error Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized (missing/invalid token)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## 🎯 Quick Reference: Most Used Routes

### For BNPL Orders & Repayments
```http
GET    /api/bnpl/orders                    # List all BNPL orders
GET    /api/bnpl/orders/{order_id}         # Get order with full repayment details
GET    /api/bnpl/applications/{id}/repayment-schedule  # Get repayment schedule
GET    /api/installments/with-history      # Get installments with history
POST   /api/installments/{id}/pay          # Pay an installment
```

### For Order Management
```http
GET    /api/orders                         # List all orders
GET    /api/orders/{id}                    # Get single order
POST   /api/orders/checkout                # Create order (Buy Now)
POST   /api/order/payment-confirmation     # Confirm payment
```

### For Loan Applications
```http
GET    /api/bnpl/applications              # List BNPL applications
GET    /api/bnpl/status/{id}               # Get application status
POST   /api/bnpl/apply                     # Submit BNPL application
```

---

**End of Frontend User Routes Guide**

