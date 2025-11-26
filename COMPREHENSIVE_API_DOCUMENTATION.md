# TrooSolar Backend - Comprehensive API Documentation

**Version:** 2.0  
**Last Updated:** November 26, 2025  
**Base URL:** `http://127.0.0.1:8000/api` (Development) / `https://troosolar.hmstech.org/api` (Production)

---

## 📋 Table of Contents

1. [Overview & Authentication](#overview--authentication)
2. [Configuration Endpoints](#configuration-endpoints)
3. [Buy Now Flow Endpoints](#buy-now-flow-endpoints)
4. [BNPL Flow Endpoints](#bnpl-flow-endpoints)
5. [Loan Calculator Endpoints](#loan-calculator-endpoints)
6. [Calendar/Scheduling Endpoints](#calendarscheduling-endpoints)
7. [Add-Ons & Services](#add-ons--services)
8. [States & Delivery Locations](#states--delivery-locations)
9. [Product Management](#product-management)
10. [Admin Endpoints](#admin-endpoints)
11. [Business Rules & Validation](#business-rules--validation)

---

## Overview & Authentication

### Authentication
All protected endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer {access_token}
```

### Response Format
**Success:**
```json
{
  "status": "success",
  "message": "Operation completed successfully",
  "data": { ... }
}
```

**Error:**
```json
{
  "status": "error",
  "message": "Error description",
  "errors": {
    "field": ["Error message"]
  }
}
```

---

## Configuration Endpoints

### 1. Get Customer Types
**Endpoint:** `GET /api/config/customer-types`  
**Auth:** Not Required

**Response:**
```json
{
  "status": "success",
  "data": [
    {"id": "residential", "label": "For Residential"},
    {"id": "sme", "label": "For SMEs"},
    {"id": "commercial", "label": "For Commercial and Industrial"}
  ],
  "message": "Customer types retrieved successfully"
}
```

### 2. Get Audit Types
**Endpoint:** `GET /api/config/audit-types`  
**Auth:** Not Required

**Response:**
```json
{
  "status": "success",
  "data": [
    {"id": "home-office", "label": "Home / Office"},
    {"id": "commercial", "label": "Commercial / Industrial"}
  ],
  "message": "Audit types retrieved successfully"
}
```

### 3. Get Loan Configuration
**Endpoint:** `GET /api/config/loan-configuration`  
**Auth:** Not Required

**Response:**
```json
{
  "status": "success",
  "data": {
    "insurance_fee_percentage": 0.50,
    "residual_fee_percentage": 1.00,
    "equity_contribution_min": 30.00,
    "equity_contribution_max": 80.00,
    "interest_rate_min": 3.00,
    "interest_rate_max": 4.00,
    "repayment_tenor_min": 1,
    "repayment_tenor_max": 12,
    "management_fee_percentage": 1.00,
    "minimum_loan_amount": 1500000.00
  },
  "message": "Loan configuration retrieved successfully"
}
```

### 4. Get Add-Ons
**Endpoint:** `GET /api/config/add-ons?order_type=buy_now`  
**Auth:** Not Required

**Query Parameters:**
- `order_type` (optional): `buy_now` | `bnpl` - Filter add-ons by order type

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Maintenance Services",
      "description": "Annual maintenance package",
      "price": 50000.00,
      "type": "service",
      "is_compulsory_bnpl": false,
      "is_compulsory_buy_now": false,
      "is_optional": true,
      "is_active": true
    },
    {
      "id": 2,
      "title": "Insurance",
      "description": "System insurance coverage",
      "price": 0.00,
      "type": "service",
      "is_compulsory_bnpl": true,
      "is_compulsory_buy_now": false,
      "is_optional": true,
      "is_active": true
    }
  ],
  "message": "Add-ons retrieved successfully"
}
```

### 5. Get States
**Endpoint:** `GET /api/config/states`  
**Auth:** Not Required

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Lagos",
      "code": "LA",
      "default_delivery_fee": 25000.00,
      "default_installation_fee": 50000.00,
      "is_active": true
    }
  ],
  "message": "States retrieved successfully"
}
```

### 6. Get Local Governments by State
**Endpoint:** `GET /api/config/local-governments?state_id=1`  
**Auth:** Not Required

**Query Parameters:**
- `state_id` (required): State ID

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "state_id": 1,
      "name": "Lagos Island",
      "delivery_fee": 25000.00,
      "installation_fee": 50000.00,
      "is_active": true
    },
    {
      "id": 2,
      "state_id": 1,
      "name": "Lagos Mainland",
      "delivery_fee": 30000.00,
      "installation_fee": 55000.00,
      "is_active": true
    }
  ],
  "message": "Local governments retrieved successfully"
}
```

### 7. Get Delivery Locations
**Endpoint:** `GET /api/config/delivery-locations?state_id=1`  
**Auth:** Not Required

**Query Parameters:**
- `state_id` (optional): Filter by state

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "state_id": 1,
      "local_government_id": 1,
      "name": "Lagos Island",
      "delivery_fee": 25000.00,
      "installation_fee": 50000.00,
      "is_active": true
    }
  ],
  "message": "Delivery locations retrieved successfully"
}
```

---

## Buy Now Flow Endpoints

### 1. Checkout & Generate Invoice
**Endpoint:** `POST /api/orders/checkout`  
**Auth:** Required

**Request Body:**
```json
{
  "product_id": 123,
  "bundle_id": null,
  "customer_type": "residential",
  "product_category": "full-kit",
  "installer_choice": "troosolar",
  "include_insurance": true,
  "include_inspection": true,
  "state_id": 1,
  "delivery_location_id": 1,
  "add_ons": [1, 2],
  "amount": 2500000
}
```

**Field Descriptions:**
- `product_id` (integer, optional): Product ID (if buying single product)
- `bundle_id` (integer, optional): Bundle ID (if buying bundle)
- `customer_type` (string, required): `residential` | `sme` | `commercial`
- `product_category` (string, required): `full-kit` | `inverter-battery` | `battery-only` | `inverter-only` | `panels-only`
- `installer_choice` (string, required): `troosolar` | `own`
- `include_insurance` (boolean, optional): Include insurance (0.5% of product price)
- `include_inspection` (boolean, optional): Include inspection fee
- `state_id` (integer, optional): State ID for delivery fee calculation
- `delivery_location_id` (integer, optional): Delivery location ID
- `add_ons` (array, optional): Array of add-on IDs
- `amount` (number, required): Product price in NGN

**Response (Success):**
```json
{
  "status": "success",
  "message": "Invoice calculated successfully",
  "data": {
    "order_id": null,
    "product_price": 2500000.00,
    "installation_fee": 50000.00,
    "material_cost": 30000.00,
    "delivery_fee": 25000.00,
    "inspection_fee": 15000.00,
    "insurance_fee": 12500.00,
    "add_ons_total": 50000.00,
    "add_ons": [
      {
        "id": 1,
        "title": "Maintenance Services",
        "price": 50000.00,
        "quantity": 1
      }
    ],
    "total": 2705000.00,
    "order_type": "buy_now",
    "installer_choice": "troosolar",
    "note": "Installation fees may change after site inspection. Any difference will be updated and shared with you for a one-off payment before installation."
  }
}
```

**Business Logic:**
- If `installer_choice === 'troosolar'`:
  - Include installation fee (from state/delivery location or default)
  - Include material cost
  - Include inspection fee if `include_inspection === true`
- If `installer_choice === 'own'`:
  - Installation fee = 0
  - Material cost = 0
  - Inspection fee = 0
- Insurance fee: 0.5% of product price if `include_insurance === true`
- Delivery fee: From delivery location, local government, state, or default
- Add-ons: Sum of selected add-on prices

---

## BNPL Flow Endpoints

### 1. Submit BNPL Application
**Endpoint:** `POST /api/bnpl/apply`  
**Auth:** Required  
**Content-Type:** `multipart/form-data`

**Request Body (FormData):**
```
customer_type: "residential"
product_category: "full-kit"
loan_amount: 2750000
repayment_duration: 12
credit_check_method: "auto"
personal_details[full_name]: "John Doe"
personal_details[bvn]: "12345678901"
personal_details[phone]: "08012345678"
personal_details[email]: "john@example.com"
personal_details[social_media]: "@johndoe" (REQUIRED - Facebook or Instagram)
property_details[state]: "Lagos"
property_details[address]: "123 Main Street"
property_details[landmark]: "Near Market"
property_details[floors]: "2"
property_details[rooms]: "4"
property_details[is_gated_estate]: "1" (1 = Yes, 0 = No)
property_details[estate_name]: "Sunshine Estate" (Required if is_gated_estate = 1)
property_details[estate_address]: "Sunshine Estate, Lagos" (Required if is_gated_estate = 1)
bank_statement: [File] (PDF, JPG, PNG - 12 months statement)
live_photo: [File] (JPG, PNG - Live photo/selfie)
```

**Validation Rules:**
- `loan_amount` >= ₦1,500,000 (minimum)
- `social_media` is **REQUIRED** (cannot proceed without it)
- `repayment_duration`: 3, 6, 9, or 12 months only
- `bvn`: Exactly 11 digits
- `is_gated_estate`: If `1`, `estate_name` and `estate_address` are required

**Response (Success):**
```json
{
  "status": "success",
  "message": "BNPL application submitted successfully. You will receive feedback within 24-48 hours.",
  "data": {
    "loan_application": {
      "id": 123,
      "user_id": 5,
      "loan_amount": 2750000.00,
      "repayment_duration": 12,
      "customer_type": "residential",
      "status": "pending",
      "social_media_handle": "@johndoe",
      "is_gated_estate": true,
      "estate_name": "Sunshine Estate",
      "created_at": "2025-11-26T10:30:00.000000Z"
    }
  }
}
```

**Error Response (Minimum Amount):**
```json
{
  "status": "error",
  "message": "Your order total does not meet the minimum ₦1,500,000 amount required for credit financing. To qualify for Buy Now, Pay Later, please add more items to your cart. Thank you."
}
```

### 2. Get Application Status
**Endpoint:** `GET /api/bnpl/status/{application_id}`  
**Auth:** Required

**Response:**
```json
{
  "status": "success",
  "data": {
    "application_id": 123,
    "status": "approved",
    "loan_amount": 2750000.00,
    "repayment_duration": 12,
    "guarantor_required": true,
    "next_step": "guarantor_form"
  },
  "message": "Application status retrieved successfully"
}
```

**Status Values:**
- `pending` - Under review (24-48 hours)
- `approved` - Approved, proceed to guarantor
- `rejected` - Application denied
- `counter_offer` - Alternative terms available

### 3. Invite/Save Guarantor
**Endpoint:** `POST /api/bnpl/guarantor/invite`  
**Auth:** Required

**Request Body:**
```json
{
  "loan_application_id": 123,
  "full_name": "Jane Doe",
  "phone": "08098765432",
  "email": "jane@example.com",
  "bvn": "98765432109",
  "relationship": "Spouse"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 456,
    "loan_application_id": 123,
    "full_name": "Jane Doe",
    "phone": "08098765432",
    "status": "pending"
  },
  "message": "Guarantor details saved successfully"
}
```

### 4. Upload Guarantor Form
**Endpoint:** `POST /api/bnpl/guarantor/upload`  
**Auth:** Required  
**Content-Type:** `multipart/form-data`

**Request Body (FormData):**
```
guarantor_id: 456
signed_form: [File] (PDF, JPG, PNG)
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "guarantor_id": 456,
    "signed_form_path": "loan_applications/guarantor_form_456.pdf"
  },
  "message": "Guarantor form uploaded successfully"
}
```

### 5. Accept Counteroffer
**Endpoint:** `POST /api/bnpl/counteroffer/accept`  
**Auth:** Required

**Request Body:**
```json
{
  "application_id": 123,
  "minimum_deposit": 750000,
  "minimum_tenor": 9
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "application_id": 123,
    "minimum_deposit": 750000,
    "minimum_tenor": 9
  },
  "message": "Counteroffer accepted successfully"
}
```

---

## Loan Calculator Endpoints

### 1. Calculate Loan
**Endpoint:** `POST /api/loan-calculation`  
**Auth:** Required

**Request Body:**
```json
{
  "loan_amount": 2000000,
  "product_amount": 2000000,
  "repayment_duration": 12,
  "deposit_percent": 30,
  "interest_rate": 4.0
}
```

**Validation:**
- `loan_amount` >= ₦1,500,000 (minimum)
- `deposit_percent`: 30-80% (minimum 30%)
- `repayment_duration`: 3, 6, 9, or 12 months only
- `interest_rate`: 3-4% monthly (configurable in backend)

**Response (Success):**
```json
{
  "status": "success",
  "message": "Loan calculated successfully",
  "data": {
    "loan_amount": 2000000.00,
    "product_amount": 2000000.00,
    "repayment_duration": 12,
    "deposit_percent": 30,
    "deposit_amount": 600000.00,
    "principal": 1400000.00,
    "interest_rate": 4.00,
    "total_interest": 672000.00,
    "total_repayment": 2072000.00,
    "monthly_payment": 172666.67,
    "management_fee": 20000.00,
    "residual_fee": 20000.00,
    "repayment_schedule": [
      {
        "month": 1,
        "principal": 116666.67,
        "interest": 56000.00,
        "monthly_repayment": 172666.67,
        "start_date": "2025-12-01",
        "end_date": "2025-12-31"
      },
      {
        "month": 2,
        "principal": 116666.67,
        "interest": 56000.00,
        "monthly_repayment": 172666.67,
        "start_date": "2026-01-01",
        "end_date": "2026-01-31"
      }
      // ... continues for all months
    ]
  }
}
```

**Calculation Formula:**
```
deposit_amount = (loan_amount * deposit_percent) / 100
principal = loan_amount - deposit_amount
total_interest = principal * (interest_rate / 100) * repayment_duration
total_repayment = principal + total_interest
monthly_payment = total_repayment / repayment_duration
management_fee = loan_amount * (management_fee_percentage / 100)
residual_fee = loan_amount * (residual_fee_percentage / 100)
```

### 2. Loan Calculator Tool (Preview)
**Endpoint:** `POST /api/loan-calculator-tool`  
**Auth:** Required

Same request/response as above, but doesn't save to database (preview only).

---

## Calendar/Scheduling Endpoints

### Get Available Slots
**Endpoint:** `GET /api/calendar/slots`  
**Auth:** Required

**Query Parameters:**
- `type` (required): `audit` | `installation`
- `payment_date` (required): Date in `YYYY-MM-DD` format

**Business Logic:**
- **Audit slots**: Available **48 hours** after payment date
- **Installation slots**: Available **72 hours** after payment date
- Slots: 9:00 AM to 5:00 PM (hourly)
- Excludes weekends (configurable)

**Example Request:**
```
GET /api/calendar/slots?type=installation&payment_date=2025-12-01
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "type": "installation",
    "payment_date": "2025-12-01",
    "start_date": "2025-12-04 09:00:00",
    "slots": [
      {
        "date": "2025-12-04",
        "time": "09:00",
        "datetime": "2025-12-04 09:00:00",
        "available": true
      },
      {
        "date": "2025-12-04",
        "time": "10:00",
        "datetime": "2025-12-04 10:00:00",
        "available": true
      }
      // ... more slots
    ],
    "message": "Available slots starting 72 hours after payment confirmation"
  },
  "message": "Available slots retrieved successfully"
}
```

---

## Add-Ons & Services

### Get Add-Ons for Order
**Endpoint:** `GET /api/add-ons?order_type=buy_now`  
**Auth:** Not Required

**Query Parameters:**
- `order_type` (optional): `buy_now` | `bnpl`

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "Maintenance Services",
      "description": "Annual maintenance package",
      "price": 50000.00,
      "type": "service",
      "is_compulsory_bnpl": false,
      "is_compulsory_buy_now": false,
      "is_optional": true
    },
    {
      "id": 2,
      "title": "Insurance",
      "description": "System insurance (0.5% of product price)",
      "price": 0.00,
      "type": "service",
      "is_compulsory_bnpl": true,
      "is_compulsory_buy_now": false,
      "is_optional": true
    },
    {
      "id": 3,
      "title": "Troosolar Special Kit",
      "description": "Special installation kit",
      "price": 30000.00,
      "type": "kit",
      "is_compulsory_bnpl": false,
      "is_compulsory_buy_now": false,
      "is_optional": true
    }
  ],
  "message": "Add-ons retrieved successfully"
}
```

**Business Rules:**
- Add-ons marked as `is_compulsory_bnpl: true` are **pre-checked and non-removable** for BNPL orders
- Add-ons marked as `is_compulsory_buy_now: true` are **pre-checked and non-removable** for Buy Now orders
- Insurance is always compulsory for BNPL (handled separately in invoice)
- Add-ons can be made optional or compulsory via backend admin panel

---

## States & Delivery Locations

### Get States
**Endpoint:** `GET /api/config/states`  
**Auth:** Not Required

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Lagos",
      "code": "LA",
      "default_delivery_fee": 25000.00,
      "default_installation_fee": 50000.00,
      "is_active": true
    }
  ],
  "message": "States retrieved successfully"
}
```

### Get Delivery Locations
**Endpoint:** `GET /api/config/delivery-locations?state_id=1`  
**Auth:** Not Required

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "state_id": 1,
      "local_government_id": 1,
      "name": "Lagos Island",
      "delivery_fee": 25000.00,
      "installation_fee": 50000.00,
      "is_active": true
    },
    {
      "id": 2,
      "state_id": 1,
      "local_government_id": 2,
      "name": "Lagos Mainland",
      "delivery_fee": 30000.00,
      "installation_fee": 55000.00,
      "is_active": true
    }
  ],
  "message": "Delivery locations retrieved successfully"
}
```

**Business Logic:**
- Delivery fee priority: Delivery Location > Local Government > State > Default
- Installation fee priority: Delivery Location > Local Government > State > Default
- Products/Bundles can be assigned to specific states (availability restriction)

---

## Product Management

### Get Most Popular Products
**Endpoint:** `GET /api/products/most-popular?category_id=1`  
**Auth:** Not Required

**Query Parameters:**
- `category_id` (optional): Filter by category

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 123,
      "title": "5KVA Solar Inverter",
      "price": 500000.00,
      "discount_price": 450000.00,
      "is_most_popular": true,
      "featured_image": "products/inverter.jpg"
    }
  ],
  "message": "Most popular products retrieved successfully"
}
```

**Business Logic:**
- Products tagged as `is_most_popular: true` appear in "Most Popular" section
- Shown when viewing products in the same category
- Similar to Amazon's "Customers also viewed" feature

---

## Admin Endpoints

### 1. Manage Add-Ons
**Endpoints:**
- `GET /api/admin/add-ons` - List all add-ons
- `POST /api/admin/add-ons` - Create add-on
- `PUT /api/admin/add-ons/{id}` - Update add-on
- `DELETE /api/admin/add-ons/{id}` - Delete add-on

**Create/Update Request:**
```json
{
  "title": "Maintenance Services",
  "description": "Annual maintenance package",
  "price": 50000.00,
  "type": "service",
  "is_compulsory_bnpl": false,
  "is_compulsory_buy_now": false,
  "is_optional": true,
  "is_active": true,
  "sort_order": 1
}
```

### 2. Manage Loan Configuration
**Endpoints:**
- `GET /api/admin/loan-configuration` - Get current configuration
- `PUT /api/admin/loan-configuration` - Update configuration

**Update Request:**
```json
{
  "insurance_fee_percentage": 0.50,
  "residual_fee_percentage": 1.00,
  "equity_contribution_min": 30.00,
  "equity_contribution_max": 80.00,
  "interest_rate_min": 3.00,
  "interest_rate_max": 4.00,
  "repayment_tenor_min": 1,
  "repayment_tenor_max": 12,
  "management_fee_percentage": 1.00,
  "minimum_loan_amount": 1500000.00
}
```

### 3. Manage States
**Endpoints:**
- `GET /api/admin/states` - List all states
- `POST /api/admin/states` - Create state
- `PUT /api/admin/states/{id}` - Update state
- `DELETE /api/admin/states/{id}` - Delete state

### 4. Manage Local Governments
**Endpoints:**
- `GET /api/admin/local-governments?state_id=1` - List by state
- `POST /api/admin/local-governments` - Create
- `PUT /api/admin/local-governments/{id}` - Update
- `DELETE /api/admin/local-governments/{id}` - Delete

### 5. Manage Delivery Locations
**Endpoints:**
- `GET /api/admin/delivery-locations` - List all
- `POST /api/admin/delivery-locations` - Create
- `PUT /api/admin/delivery-locations/{id}` - Update
- `DELETE /api/admin/delivery-locations/{id}` - Delete

### 6. Assign Products/Bundles to States
**Endpoints:**
- `POST /api/admin/products/{id}/assign-states` - Assign product to states
- `POST /api/admin/bundles/{id}/assign-states` - Assign bundle to states

**Request:**
```json
{
  "state_ids": [1, 2, 3]
}
```

### 7. Toggle Most Popular Products
**Endpoint:** `PUT /api/admin/products/{id}/toggle-popular`

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 123,
    "is_most_popular": true
  },
  "message": "Product popularity updated successfully"
}
```

---

## Business Rules & Validation

### BNPL Rules

1. **Minimum Loan Amount:** ₦1,500,000 (configurable in backend)
2. **Social Media:** **REQUIRED** - Cannot proceed without Facebook/Instagram handle
3. **Insurance:** **Compulsory** for BNPL (0.5% of product price)
4. **Installation:** **Compulsory** for BNPL (pre-checked, non-removable)
5. **Equity Contribution:** Minimum 30% upfront payment
6. **Repayment Tenor:** 3, 6, 9, or 12 months only
7. **Interest Rate:** 3-4% monthly (configurable)
8. **Residual Fee:** 1% of loan amount (paid at end of tenor)
9. **Management Fee:** 1% of loan amount
10. **Gated Estate:** If Yes, estate name and address are required

### Buy Now Rules

1. **Insurance:** **Optional** (tickable checkbox)
2. **Installation:** **Optional** (can choose Troosolar or own installer)
3. **Inspection:** **Optional** (only if using Troosolar installer)
4. **Calendar Slots:** Available 72 hours after payment confirmation
5. **Full Payment:** Can pay upfront and schedule installation within 24 hours

### Loan Calculator Rules

1. **Minimum Amount:** ₦1,500,000
2. **Deposit:** 30-80% (minimum 30%)
3. **Tenor:** 3, 6, 9, or 12 months only
4. **Interest Rate:** 3-4% monthly (configurable)
5. **Repayment Schedule:** Auto-generated for all months

### Commercial Audit Rules

1. **DO NOT** generate instant invoice for commercial audits
2. Trigger admin notification for manual follow-up
3. Admin contacts customer within 24-48 hours

### File Upload Rules

- **Bank Statement:** PDF, JPG, PNG (max 10MB) - 12 months statement
- **Live Photo:** JPG, PNG (max 5MB) - Selfie/live photo
- **Guarantor Form:** PDF, JPG, PNG (max 10MB) - Signed form

### Validation Rules

- **BVN:** Exactly 11 digits, numeric only
- **Email:** Valid email format
- **Phone:** Nigerian phone format (e.g., 08012345678)
- **Social Media:** Required, must be provided (Facebook or Instagram handle)

---

## Order Summary & Invoice Structure

### BNPL Order Summary (Before Invoice)

```json
{
  "items": [
    {
      "description": "5KVA Solar Inverter",
      "quantity": 1,
      "price": 500000.00
    },
    {
      "description": "Solar Panels 300W",
      "quantity": 10,
      "price": 1500000.00
    },
    {
      "description": "Batteries 200Ah",
      "quantity": 4,
      "price": 800000.00
    }
  ],
  "appliances": "Refrigerator, TV, Fans, Lights",
  "backup_time": "8-10 hours"
}
```

### BNPL Invoice (Before Checkout)

```json
{
  "invoice": {
    "solar_inverter": {
      "quantity": 1,
      "price": 500000.00,
      "subtotal": 500000.00
    },
    "solar_panels": {
      "quantity": 10,
      "price": 150000.00,
      "subtotal": 1500000.00
    },
    "batteries": {
      "quantity": 4,
      "price": 200000.00,
      "subtotal": 800000.00
    },
    "material_cost": 50000.00,
    "installation_fee": 50000.00,
    "delivery_fee": 25000.00,
    "inspection_fee": 10000.00,
    "insurance_fee": 14000.00,
    "subtotal": 2949000.00,
    "total": 2949000.00,
    "equity_contribution_min": 884700.00,
    "equity_contribution_percent": 30,
    "note": "Material/installation costs may change after site inspection. Any difference will be updated and shared with you for a one-off payment before installation."
  }
}
```

**Note:** "Minimum of 30% Upfront Payment Required" must be highlighted before proceeding.

### Buy Now Order Summary

**If installation = No:**
```json
{
  "solar_inverter": {"quantity": 1, "price": 500000.00},
  "batteries": {"quantity": 4, "price": 800000.00},
  "delivery_fee": 25000.00,
  "insurance_fee": 6500.00,
  "total": 1331500.00
}
```

**If installation = Yes (Troosolar):**
```json
{
  "solar_inverter": {"quantity": 1, "price": 500000.00},
  "solar_panels": {"quantity": 10, "price": 1500000.00},
  "batteries": {"quantity": 4, "price": 800000.00},
  "material_cost": 50000.00,
  "installation_fee": 50000.00,
  "delivery_fee": 25000.00,
  "inspection_fee": 10000.00,
  "insurance_fee": 14000.00,
  "total": 2949000.00,
  "note": "Installation fees may change after site inspection..."
}
```

---

## Route Placement Guide

### Public Routes (Before `auth:sanctum`)
```php
Route::get('/config/customer-types', [ConfigurationController::class, 'getCustomerTypes']);
Route::get('/config/audit-types', [ConfigurationController::class, 'getAuditTypes']);
Route::get('/config/loan-configuration', [ConfigurationController::class, 'getLoanConfiguration']);
Route::get('/config/add-ons', [ConfigurationController::class, 'getAddOns']);
Route::get('/config/states', [ConfigurationController::class, 'getStates']);
Route::get('/config/local-governments', [ConfigurationController::class, 'getLocalGovernments']);
Route::get('/config/delivery-locations', [ConfigurationController::class, 'getDeliveryLocations']);
Route::get('/add-ons', [AddOnController::class, 'index']);
Route::get('/products/most-popular', [ProductController::class, 'mostPopular']);
```

### Protected Routes (Inside `auth:sanctum`)
```php
// Buy Now
Route::post('/orders/checkout', [OrderController::class, 'checkout']);

// BNPL
Route::post('/bnpl/apply', [BNPLController::class, 'apply']);
Route::get('/bnpl/status/{application_id}', [BNPLController::class, 'getStatus']);
Route::post('/bnpl/guarantor/invite', [BNPLController::class, 'inviteGuarantor']);
Route::post('/bnpl/guarantor/upload', [BNPLController::class, 'uploadGuarantorForm']);
Route::post('/bnpl/counteroffer/accept', [BNPLController::class, 'acceptCounterOffer']);

// Loan Calculator
Route::post('/loan-calculation', [LoanCalculationController::class, 'store']);
Route::post('/loan-calculator-tool', [LoanCalculationController::class, 'tool']);

// Calendar
Route::get('/calendar/slots', [CalendarController::class, 'getSlots']);
```

### Admin Routes (Inside `auth:sanctum` with admin role check)
```php
Route::prefix('admin')->group(function () {
    Route::apiResource('add-ons', AddOnController::class);
    Route::get('loan-configuration', [LoanConfigurationController::class, 'show']);
    Route::put('loan-configuration', [LoanConfigurationController::class, 'update']);
    Route::apiResource('states', StateController::class);
    Route::apiResource('local-governments', LocalGovernmentController::class);
    Route::apiResource('delivery-locations', DeliveryLocationController::class);
    Route::post('products/{id}/assign-states', [ProductController::class, 'assignStates']);
    Route::post('bundles/{id}/assign-states', [BundleController::class, 'assignStates']);
    Route::put('products/{id}/toggle-popular', [ProductController::class, 'togglePopular']);
});
```

---

## AI Chatbot Feature

### Recommendation
For AI chatbot integration, consider:

1. **OpenAI GPT-4 API** - Best for natural language understanding
   - Cost: ~$0.03 per 1K tokens (input), ~$0.06 per 1K tokens (output)
   - Estimated monthly cost: $50-200 (depending on usage)

2. **Custom Fine-tuned Model** - Trained on TrooSolar data
   - Initial setup: $500-1000
   - Monthly hosting: $100-300

3. **Hybrid Approach** - Rule-based + AI
   - Initial development: $2000-5000
   - Monthly API costs: $30-100

**Implementation Steps:**
1. Create knowledge base from website content
2. Train/fine-tune model on TrooSolar-specific data
3. Integrate with website chat widget
4. Set up escalation to human support when needed
5. Monitor and improve based on conversations

**Endpoints Needed:**
- `POST /api/chatbot/message` - Send message to chatbot
- `GET /api/chatbot/escalate` - Escalate to human support
- `POST /api/admin/chatbot/knowledge-base` - Update knowledge base

---

## Testing Checklist

### Buy Now Flow
- [ ] Checkout with product
- [ ] Checkout with bundle
- [ ] Checkout with Troosolar installer
- [ ] Checkout with own installer
- [ ] Checkout with insurance
- [ ] Checkout without insurance
- [ ] Checkout with add-ons
- [ ] Calendar slots (72 hours)
- [ ] Delivery fee calculation by location

### BNPL Flow
- [ ] Application with minimum amount validation
- [ ] Application with social media (required)
- [ ] Application with gated estate (Yes/No)
- [ ] File uploads (bank statement, live photo)
- [ ] Application status check
- [ ] Guarantor invite
- [ ] Guarantor form upload
- [ ] Counteroffer acceptance
- [ ] Calendar slots (48 hours for audit)

### Loan Calculator
- [ ] Minimum amount validation (₦1.5M)
- [ ] Deposit percent (30-80%)
- [ ] Tenor validation (3, 6, 9, 12 months)
- [ ] Interest rate (3-4%)
- [ ] Repayment schedule generation
- [ ] Management fee calculation
- [ ] Residual fee calculation

### Configuration
- [ ] Get customer types
- [ ] Get audit types
- [ ] Get loan configuration
- [ ] Get add-ons
- [ ] Get states
- [ ] Get local governments
- [ ] Get delivery locations

---

## Support & Contact

For questions or issues:
- Backend Team: [Contact Info]
- API Support: [Contact Info]

**Last Updated:** November 26, 2025  
**Documentation Version:** 2.0

