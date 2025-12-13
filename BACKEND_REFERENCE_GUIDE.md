# TrooSolar Backend - Complete Reference Guide
**Last Updated:** 2024-12-27  
**Purpose:** Internal reference for understanding application flows, routes, models, and relationships

---

## 📑 Table of Contents

1. [Application Architecture](#application-architecture)
2. [Authentication & User Management](#authentication--user-management)
3. [Product & Catalog System](#product--catalog-system)
4. [Buy Now Flow](#buy-now-flow)
5. [BNPL (Buy Now Pay Later) Flow](#bnpl-buy-now-pay-later-flow)
6. [Loan Management System](#loan-management-system)
7. [Audit Request System](#audit-request-system)
8. [Order Management](#order-management)
9. [Cart System](#cart-system)
10. [Admin Management](#admin-management)
11. [Support & Communication](#support--communication)
12. [Database Models & Relationships](#database-models--relationships)
13. [Route Organization](#route-organization)
14. [Key Business Rules](#key-business-rules)

---

## 🏗️ Application Architecture

### Controller Organization
```
app/Http/Controllers/
├── Api/
│   ├── Admin/          # Admin-only endpoints
│   │   ├── UserController
│   │   ├── BNPLAdminController
│   │   ├── AuditAdminController
│   │   ├── ProductController
│   │   ├── CategoryController
│   │   ├── BrandController
│   │   ├── BundleController
│   │   ├── OrderController (admin methods)
│   │   └── ... (other admin controllers)
│   └── Website/        # User-facing endpoints
│       ├── OrderController
│       ├── BNPLController
│       ├── AuditController
│       ├── LoanCalculationController
│       ├── CartController
│       └── ... (other website controllers)
├── KycController       # KYC verification (user + admin)
├── ReferralController  # Referral system
└── InstallmentController # Loan installments
```

### Authentication
- **Method:** Laravel Sanctum (API Tokens)
- **Middleware:** `auth:sanctum` on protected routes
- **User Roles:**
  - `user` - Default for new registrations
  - `admin` - Admin access
  - `super_admin` - Super admin
  - `compliance` - KYC reviewer
  - `kyc_reviewer` - KYC reviewer

---

## 🔐 Authentication & User Management

### User Registration & Login Flow

#### Public Routes (No Auth Required)
```
POST   /api/register
POST   /api/verify-otp/{user_id}
POST   /api/login
POST   /api/admin-login
POST   /api/forget-password
POST   /api/verify-reset-password-otp
POST   /api/reset-password
```

#### Protected Routes (Auth Required)
```
POST   /api/logout
POST   /api/update-user
GET    /api/send-otp
GET    /api/single-user/{user_id}
DELETE /api/delete-user/{user_id}
```

### Key User Model Fields
- `first_name`, `sur_name`, `email`, `password`
- `phone`, `profile_picture`, `user_code`
- `role` (default: 'user')
- `is_verified`, `is_active`
- `otp`, `bvn`

### User Relationships
- `hasOne(Wallet)` - User wallet
- `hasMany(LoanApplication)` - Loan applications
- `hasMany(Order)` - Orders
- `hasMany(UserActivity)` - Activity logs

---

## 📦 Product & Catalog System

### Hierarchy
```
Category
  └── Brand
       └── Product
```

### Models
- **Category** - Product categories (Inverter, Battery, Panels, etc.)
- **Brand** - Product brands (belongs to Category)
- **Product** - Individual products (belongs to Category & Brand)
- **Bundles** - Pre-configured product bundles
- **BundleItems** - Products in a bundle (many-to-many)

### Key Routes
```
GET    /api/categories                    # List all categories
GET    /api/categories/{id}/brands        # Brands by category
GET    /api/categories/{id}/products      # Products by category
GET    /api/brands/{id}/products          # Products by brand
GET    /api/bundles                       # Public bundles list
GET    /api/products/top-products         # Top deals/products
```

### Product Fields
- `title`, `price`, `discount_price`
- `category_id`, `brand_id`
- `stock`, `installation_price`
- `top_deal`, `installation_compulsory`
- `featured_image`, `is_most_popular`

---

## 🛒 Buy Now Flow

### Flow Overview
1. **Customer Type Selection** → `residential|sme|commercial`
2. **Product Category Selection** → `full-kit|inverter-battery|battery-only|inverter-only|panels-only`
3. **Method Selection** (if full-kit/inverter-battery) → `choose-system|build-system|audit`
4. **Checkout Options** → Installer choice, Insurance, Add-ons
5. **Invoice Generation** → Order creation
6. **Payment** → Payment gateway integration
7. **Calendar Booking** → Installation scheduling

### Key Endpoints
```
POST   /api/orders/checkout               # Create order & generate invoice
GET    /api/orders/{id}/summary           # Order summary with items
GET    /api/orders/{id}/invoice-details   # Detailed invoice breakdown
POST   /api/order/payment-confirmation    # Confirm payment (type: direct|audit)
GET    /api/calendar/slots                # Get available slots (type: installation|audit)
```

### Order Creation Process
1. Validate product/bundle or accept `amount` directly
2. Calculate fees:
   - Material cost (product price)
   - Installation fee (based on `installer_choice` and `state_id`)
   - Delivery fee (based on `delivery_location_id`)
   - Insurance fee (0.5% if included)
   - Inspection fee (if included)
3. Process add-ons if provided
4. Create `Order` record with `order_type: 'buy_now'`
5. Return order ID and invoice breakdown

### Order Fields
- `order_type` - `buy_now|bnpl|audit_only`
- `product_id`, `bundle_id`
- `material_cost`, `installation_price`, `delivery_fee`
- `insurance_fee`, `inspection_fee`
- `total_price`, `payment_status`, `order_status`
- `state_id`, `delivery_location_id`
- `audit_request_id` (if audit order)

---

## 💳 BNPL (Buy Now Pay Later) Flow

### Flow Overview (21 Steps)
1. Customer Type Selection
2. Product Category Selection
3. Method Selection (if applicable)
4. Audit Type Selection (if audit chosen)
5-7. Property Details & Audit Invoice
8. Loan Calculator (deposit %, tenor, interest)
9. Loan Calculation Creation
10. BNPL Application Submission
11. Admin Review (pending → approved/rejected/counter_offer)
12. Guarantor Invitation (if approved)
13. Guarantor Form Upload
14. Calendar Booking
15. Payment Confirmation
16. Order Fulfillment

### Key Endpoints
```
POST   /api/loan-calculation              # Create loan calculation
POST   /api/loan-calculation-finalized/{id} # Finalize calculation
GET    /api/loan-calculation-stauts       # Check calculation status
GET    /api/offered-loan-calculation      # Get offered calculation
POST   /api/bnpl/apply                    # Submit BNPL application
GET    /api/bnpl/status/{application_id}  # Check application status
POST   /api/bnpl/guarantor/invite         # Invite guarantor
POST   /api/bnpl/guarantor/upload         # Upload guarantor form
POST   /api/bnpl/counteroffer/accept      # Accept counter offer
```

### Loan Calculation Flow
1. User configures loan (deposit %, tenor: 3|6|9|12 months)
2. Frontend calculates: deposit, principal, interest, monthly payment
3. `POST /api/loan-calculation` - Creates `LoanCalculation` with status `calculated`
4. Admin reviews → Creates `MonoLoanCalculation` with status `offered`
5. User finalizes → Status becomes `finalized`
6. BNPL application links to `MonoLoanCalculation`

### BNPL Application Fields
- `customer_type`, `product_category`, `loan_amount`
- `repayment_duration`, `credit_check_method`
- `personal_details` (full_name, bvn, phone, email, social_media)
- `property_details` (state, address, floors, rooms, gated_estate)
- `bank_statement_path`, `live_photo_path`
- `status` - `pending|approved|rejected|counter_offer`
- `mono_loan_calculation` (links to MonoLoanCalculation)

### Minimum Loan Amount
- **₦1,500,000** - Minimum required for BNPL

### Guarantor Process
1. After approval, user invites guarantor
2. Guarantor receives invitation (email/phone)
3. Guarantor uploads signed form
4. Admin reviews guarantor documents
5. Status: `pending|approved|rejected`

---

## 📋 Loan Management System

### Models & Flow
```
LoanCalculation (initial calculation)
    ↓ (admin creates)
MonoLoanCalculation (offered loan)
    ↓ (user applies)
LoanApplication (BNPL application)
    ↓ (approved)
LoanStatus (tracking)
    ↓ (distributed)
LoanInstallment (monthly payments)
    ↓
LoanRepayment (payment records)
```

### Key Endpoints
```
POST   /api/mono-loan/{loanCalculationId}    # Admin: Create offered loan
POST   /api/mono-loan/edit/{loanCalculationId} # Admin: Edit offered loan
POST   /api/loan-application-grant/{id}      # Admin: Grant loan
POST   /api/loan-application/{monoLoanCalculationId} # Submit documents
POST   /api/beneficiary-detail/{monoLoanCalculationId} # Add beneficiary
POST   /api/loan-details/{monoLoanCalculationId} # Add loan details
GET    /api/all-loan-application             # List all applications
GET    /api/single-loan-application/{id}     # Get single application
POST   /api/loan-installment/{monoLoanCalculationId} # Create installments
GET    /api/show-loan-installment/{monoCalculationId} # Get installments
POST   /api/loan-repayment/{monoLoanCalculationId} # Record repayment
POST   /api/loan-distributed/{loanCalculationId} # Mark as distributed
GET    /api/all-loan-distributed             # List distributed loans
GET    /api/installments/with-history        # Installments with history
POST   /api/installments/{installmentId}/pay # Pay installment
```

### Loan Calculation Statuses
- `calculated` - User calculated loan
- `pending` - Awaiting admin review
- `offered` - Admin offered loan terms
- `finalized` - User accepted offer

### Loan Application Statuses
- `pending` - Under review
- `approved` - Approved, proceed to guarantor
- `rejected` - Application denied
- `counter_offer` - Alternative terms available

---

## 🏠 Audit Request System

### Flow
1. User selects audit type (`home-office|commercial`)
2. Fills property details
3. Creates audit request (status: `pending`)
4. For `home-office`: Creates audit order immediately
5. For `commercial`: Admin notification, no instant invoice
6. User pays audit fee
7. Admin approves/rejects
8. Calendar booking available 48 hours after payment

### Key Endpoints
```
POST   /api/audit/request                  # Submit audit request
GET    /api/audit/request/{id}             # Get audit status
GET    /api/audit/requests                 # List user's audit requests
```

### Admin Endpoints
```
GET    /api/admin/audit/requests           # List all audit requests
GET    /api/admin/audit/requests/{id}      # Get single audit request
PUT    /api/admin/audit/requests/{id}/status # Approve/reject (status: approved|rejected|completed)
```

### Audit Request Fields
- `audit_type` - `home-office|commercial`
- `customer_type` - `residential|sme|commercial`
- `property_state`, `property_address`, `property_landmark`
- `property_floors`, `property_rooms`
- `is_gated_estate`, `estate_name`, `estate_address`
- `status` - `pending|approved|rejected|completed`
- `order_id` - Links to audit order

### Audit Fee Calculation
- **Home/Office Base:** ₦50,000
- **Commercial Base:** ₦100,000
- **Size Fee:** (floors × multiplier) + (rooms × multiplier)
- **Cap:** ₦200,000 (home) / ₦500,000 (commercial)

---

## 📦 Order Management

### Order Types
- `buy_now` - Direct purchase
- `bnpl` - Buy Now Pay Later
- `audit_only` - Audit fee order

### Order Statuses
- `pending` - Order placed, awaiting payment
- `confirmed` - Payment confirmed
- `processing` - Being processed
- `shipped` - Shipped
- `delivered` - Delivered
- `cancelled` - Cancelled

### Payment Statuses
- `pending` - Awaiting payment
- `confirmed` - Payment confirmed
- `failed` - Payment failed
- `refunded` - Refunded

### Key Endpoints
```
GET    /api/orders                         # List orders (user/admin)
POST   /api/orders                         # Create order from cart
GET    /api/orders/{id}                    # Get single order
DELETE /api/orders/{id}                    # Cancel order
GET    /api/orders/user/{userId}           # Get user's orders
POST   /api/order/payment-confirmation     # Confirm payment
POST   /api/order/pay-by-loan              # Pay using loan wallet
```

### Admin Order Management
```
GET    /api/admin/orders/buy-now           # List Buy Now orders
GET    /api/admin/orders/buy-now/{id}      # Get Buy Now order
PUT    /api/admin/orders/buy-now/{id}/status # Update status
GET    /api/admin/orders/bnpl              # List BNPL orders
GET    /api/admin/orders/bnpl/{id}         # Get BNPL order
```

---

## 🛍️ Cart System

### Key Endpoints
```
GET    /api/cart                           # Get user's cart
POST   /api/cart                           # Add item to cart
PUT    /api/cart/{id}                      # Update cart item quantity
DELETE /api/cart/{id}                      # Remove item from cart
DELETE /api/cart                           # Clear entire cart
GET    /api/cart/checkout-summary          # Get checkout summary
```

### Cart Item Types
- `Product` - Single product
- `Bundle` - Product bundle
- Uses polymorphic relationship (`itemable`)

---

## 👨‍💼 Admin Management

### BNPL Admin
```
GET    /api/admin/bnpl/applications        # List BNPL applications
GET    /api/admin/bnpl/applications/{id}   # Get single application
PUT    /api/admin/bnpl/applications/{id}/status # Update status
GET    /api/admin/bnpl/guarantors          # List guarantors
PUT    /api/admin/bnpl/guarantors/{id}/status # Update guarantor status
```

### Product Management
```
GET    /api/products                       # List products
POST   /api/products                       # Create product
GET    /api/products/{id}                  # Get product
PUT    /api/products/{id}                  # Update product
POST   /api/products/{product}/update      # Alternative update
DELETE /api/products/{id}                  # Delete product
```

### User Management
```
GET    /api/all-users                      # List all users
GET    /api/total-users                    # Get user statistics
GET    /api/admin/users/with-loans         # Users with loans
POST   /api/add-user                       # Admin: Create user
POST   /api/admin/user/edit-user/{userId}  # Admin: Update user
```

### Dashboard & Analytics
```
GET    /api/admin/dashboard                # Admin dashboard
GET    /api/admin/analytics                # Analytics data
```

---

## 💬 Support & Communication

### Tickets
```
GET    /api/website/tickets                # List user tickets
POST   /api/website/tickets                # Create ticket
GET    /api/website/tickets/{id}           # Get ticket
PUT    /api/website/tickets/{id}           # Update ticket
DELETE /api/website/tickets/{id}           # Delete ticket
```

### Admin Tickets
```
GET    /api/admin/tickets                  # List all tickets
POST   /api/admin/tickets                  # Create ticket
GET    /api/admin/tickets/{id}             # Get ticket
PUT    /api/admin/tickets/{id}             # Update ticket
POST   /api/admin/tickets/{ticket}/reply   # Admin reply
POST   /api/admin/tickets/{ticketId}/status # Update status
```

### Notifications
```
GET    /api/user-notifications             # User notifications
GET    /api/admin/notifications            # Admin notifications
```

---

## 🗄️ Database Models & Relationships

### Core Models

#### User
```php
Relationships:
- hasOne(Wallet)
- hasMany(Order)
- hasMany(LoanApplication)
- hasMany(UserActivity)
- hasMany(LoanHistory)
- hasMany(LoanInstallment)
- hasMany(LoanRepayment)
```

#### Order
```php
Relationships:
- belongsTo(User)
- belongsTo(Product) [optional]
- belongsTo(Bundles, 'bundle_id') [optional]
- belongsTo(DeliveryAddress)
- belongsTo(MonoLoanCalculation, 'mono_calculation_id') [optional]
- belongsTo(AuditRequest) [optional]
- hasMany(OrderItem)
```

#### LoanCalculation
```php
Relationships:
- belongsTo(User)
- hasOne(MonoLoanCalculation, 'loan_calculation_id')
- hasOne(LoanDistribute)
```

#### MonoLoanCalculation
```php
Relationships:
- belongsTo(LoanCalculation)
- hasMany(LoanInstallment)
- hasMany(LoanRepayment, 'mono_calculation_id')
- hasOne(Order, 'mono_calculation_id')
```

#### LoanApplication
```php
Relationships:
- belongsTo(User)
- hasOne(MonoLoanCalculation, 'mono_loan_calculation')
- hasOne(Guarantor)
- hasOne(LoanStatus)
- hasMany(LoanHistory)
- hasMany(LoanInstallment, 'mono_calculation_id', 'mono_loan_calculation')
```

#### Guarantor
```php
Relationships:
- belongsTo(User)
- belongsTo(LoanApplication)
```

#### AuditRequest
```php
Relationships:
- belongsTo(User)
- belongsTo(Order)
- belongsTo(User, 'approved_by') // approver
```

#### Wallet
```php
Relationships:
- belongsTo(User)
- hasMany(Transaction, 'user_id', 'user_id')
```

### Product Models

#### Category → Brand → Product
```
Category
  └── hasMany(Brand)
       └── hasMany(Product)
```

#### Bundles
```
Bundles
  └── hasMany(BundleItems)
       └── belongsTo(Product)
```

---

## 🛣️ Route Organization

### Public Routes (No Auth)
```
POST   /api/register
POST   /api/login
POST   /api/admin-login
GET    /api/config/customer-types
GET    /api/config/audit-types
GET    /api/config/states
GET    /api/config/loan-configuration
GET    /api/config/add-ons
GET    /api/config/delivery-locations
GET    /api/bundles
```

### Protected Routes (Auth Required)

#### User Routes
```
# Orders
POST   /api/orders/checkout
GET    /api/orders/{id}/summary
GET    /api/orders/{id}/invoice-details

# BNPL
POST   /api/loan-calculation
POST   /api/bnpl/apply
GET    /api/bnpl/status/{application_id}
POST   /api/bnpl/guarantor/invite
POST   /api/bnpl/guarantor/upload

# Audit
POST   /api/audit/request
GET    /api/audit/request/{id}
GET    /api/audit/requests

# Calendar
GET    /api/calendar/slots

# Cart
GET/POST/PUT/DELETE /api/cart
```

#### Admin Routes (Admin Only)
```
# BNPL Admin
GET    /api/admin/bnpl/applications
PUT    /api/admin/bnpl/applications/{id}/status

# Audit Admin
GET    /api/admin/audit/requests
PUT    /api/admin/audit/requests/{id}/status

# Order Admin
GET    /api/admin/orders/buy-now
PUT    /api/admin/orders/buy-now/{id}/status
```

---

## ⚙️ Key Business Rules

### BNPL Rules
1. **Minimum Loan Amount:** ₦1,500,000
2. **Tenor Options:** 3, 6, 9, or 12 months
3. **Deposit Range:** 30-80% of product amount
4. **Interest Rate:** Configurable (typically 4% monthly)
5. **BVN Required:** Yes (11 characters)
6. **Social Media Required:** Yes (for loan applications)
7. **Guarantor Required:** After approval

### Buy Now Rules
1. **Installation Options:** TrooSolar Installer or Own Installer
2. **Insurance:** Optional (0.5% of product price)
3. **Add-ons:** Optional products/services
4. **Delivery Fees:** Based on state and delivery location
5. **Installation Fees:** Based on state and installer choice

### Audit Rules
1. **Home/Office:** Instant invoice generation
2. **Commercial:** Admin notification, no instant invoice
3. **Calendar Booking:** Available 48 hours after payment
4. **Fee Calculation:** Based on floors and rooms

### Order Rules
1. **Order Types:** `buy_now`, `bnpl`, `audit_only`
2. **Payment Methods:** `direct`, `loan`
3. **Payment Confirmation:** Via payment gateway callback

### Loan Calculation Flow
1. User calculates → Status: `calculated`
2. Admin offers → Creates `MonoLoanCalculation`, Status: `offered`
3. User finalizes → Status: `finalized`
4. BNPL application links to `MonoLoanCalculation`

### User Roles & Permissions
- `user` - Default role, standard access
- `admin` - Admin panel access
- `super_admin` - Full system access
- Admin checks done via `$user->role == 'admin'` or middleware

---

## 🔄 Key Workflows

### Buy Now Complete Flow
```
1. Select customer type
2. Select product category
3. (Optional) Select method (choose-system/audit)
4. Configure checkout options (installer, insurance, add-ons)
5. POST /api/orders/checkout → Order created
6. Payment via gateway
7. POST /api/order/payment-confirmation → Payment confirmed
8. GET /api/calendar/slots → Book installation
```

### BNPL Complete Flow
```
1. Select customer type & product category
2. (Optional) Audit flow if selected
3. Configure loan calculator (deposit %, tenor)
4. POST /api/loan-calculation → Calculation created
5. Admin reviews → Creates MonoLoanCalculation (offered)
6. POST /api/bnpl/apply → Application submitted
7. Admin reviews → Status: approved/rejected/counter_offer
8. (If approved) POST /api/bnpl/guarantor/invite
9. POST /api/bnpl/guarantor/upload → Guarantor form
10. Admin approves guarantor
11. GET /api/calendar/slots → Book installation
12. Order fulfillment
```

### Audit Flow
```
1. Select audit type (home-office/commercial)
2. Fill property details
3. POST /api/audit/request → Request created
4. (Home-office) Order created automatically
5. (Commercial) Admin notification, no order
6. Payment (if home-office)
7. POST /api/order/payment-confirmation (type: audit)
8. Admin approves/rejects
9. GET /api/calendar/slots (48h after payment) → Book audit
```

---

## 📝 Important Notes

### File Uploads
- **Bank Statement:** PDF, JPG, PNG (max 10MB)
- **Live Photo:** JPG, PNG (max 5MB)
- **Guarantor Form:** PDF, JPG, PNG (max 10MB)
- **Profile Picture:** Image (max 2MB)

### Date Formats
- API expects: `YYYY-MM-DD`
- Laravel Carbon used for date handling

### Error Responses
- Validation errors: `422 Unprocessable Entity`
- Not found: `404 Not Found`
- Unauthorized: `401 Unauthorized`
- Server errors: `500 Internal Server Error`

### Response Format
```json
{
  "status": "success|error",
  "data": {...},
  "message": "Human-readable message"
}
```

---

## 🔍 Quick Reference: Route Prefixes

- `/api/config/*` - Public configuration endpoints
- `/api/admin/*` - Admin-only endpoints
- `/api/website/*` - User-facing website endpoints
- `/api/bnpl/*` - BNPL specific endpoints
- `/api/audit/*` - Audit request endpoints
- `/api/orders/*` - Order management
- `/api/cart/*` - Shopping cart
- `/api/loan-*` - Loan calculation and management

---

## 🎯 When Adding New Features

1. **Identify Flow:** Buy Now, BNPL, Audit, or Other
2. **Check Models:** Which models are involved?
3. **Follow Patterns:** Use existing controllers as templates
4. **Update Routes:** Add to appropriate section in `routes/api.php`
5. **Validate Input:** Use Form Request classes
6. **Error Handling:** Use `ResponseHelper` for consistent responses
7. **Logging:** Use `Log::` for important operations
8. **Relationships:** Ensure model relationships are correct
9. **Admin Panel:** If admin action needed, add to Admin controllers

---

**End of Reference Guide**

