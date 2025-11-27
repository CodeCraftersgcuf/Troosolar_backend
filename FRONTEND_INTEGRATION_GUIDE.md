# TrooSolar Frontend Integration Guide

**Date:** November 26, 2025  
**Status:** All Routes Implemented and Ready for Integration

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Complete Route List](#complete-route-list)
4. [Detailed Endpoint Documentation](#detailed-endpoint-documentation)
5. [Integration Flow Examples](#integration-flow-examples)
6. [Response Format Handling](#response-format-handling)
7. [Error Handling](#error-handling)
8. [Testing Checklist](#testing-checklist)

---

## 🎯 Overview

This guide provides complete documentation for all API endpoints used in the **Buy Now** and **BNPL (Buy Now, Pay Later)** flows. All routes have been implemented and tested.

**Base URL:**
- Development: `http://127.0.0.1:8000/api`
- Production: `https://troosolar.hmstech.org/api`

---

## 🔐 Authentication

### Public Routes (No Authentication)
- `GET /api/config/customer-types`
- `GET /api/config/audit-types`
- `GET /api/config/states`
- `GET /api/bundles`

### Protected Routes (Require Authentication)
All other routes require Bearer token authentication:

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
Accept: application/json
```

**Getting Access Token:**
```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Login Successfully",
  "token": "1|xxxxxxxxxxxxx",
  "token_type": "bearer",
  "user": { ... }
}
```

---

## 📡 Complete Route List

### Configuration Routes (Public)
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/config/customer-types` | Get customer type options |
| GET | `/api/config/audit-types` | Get audit type options |
| GET | `/api/config/states` | Get all states |

### Buy Now Flow Routes (Protected)
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/bundles` | Get available bundles |
| POST | `/api/orders/checkout` | Generate invoice/order |
| POST | `/api/order/payment-confirmation` | Confirm payment |
| GET | `/api/calendar/slots?type=installation&payment_date={date}` | Get installation slots |

### BNPL Flow Routes (Protected)
| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/loan-calculation` | Create loan calculation |
| POST | `/api/bnpl/apply` | Submit BNPL application |
| GET | `/api/bnpl/status/{application_id}` | Get application status |
| POST | `/api/bnpl/guarantor/invite` | Save guarantor details |
| POST | `/api/bnpl/guarantor/upload` | Upload signed guarantor form |
| POST | `/api/bnpl/counteroffer/accept` | Accept counter offer |
| POST | `/api/order/payment-confirmation` | Confirm audit payment |
| GET | `/api/calendar/slots?type=audit&payment_date={date}` | Get audit slots |

---

## 📖 Detailed Endpoint Documentation

### 1. Loan Calculation (BNPL Flow - Step 9)

**Endpoint:** `POST /api/loan-calculation`

**Purpose:** Create loan calculation before submitting BNPL application. This must be called BEFORE `POST /api/bnpl/apply`.

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "product_amount": 2500000.00,
  "loan_amount": 2750000.00,
  "repayment_duration": 12
}
```

**Field Descriptions:**
- `product_amount` (number, required): Total product price including fees
- `loan_amount` (number, required): Total loan amount (principal + interest + fees)
- `repayment_duration` (number, required): Tenor in months (3, 6, 9, or 12)

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Loan calculation created successfully",
  "repayment_date": "2025-12-26T10:00:00.000000Z",
  "id": 123,
  "data": {
    "id": 123,
    "user_id": 2,
    "product_amount": 2500000.00,
    "loan_amount": 2750000.00,
    "repayment_duration": 12,
    "monthly_payment": 229166.67,
    "interest_percentage": 4.00,
    "interest_rate": 4.00,
    "down_payment": 57291.67,
    "total_amount": 2750000.00,
    "deposit_amount": 825000.00,
    "principal": 1925000.00,
    "total_interest": 825000.00,
    "monthly_repayment": 229166.67,
    "total_repayment": 2750000.00,
    "status": "calculated",
    "created_at": "2025-11-26T10:00:00.000000Z",
    "updated_at": "2025-11-26T10:00:00.000000Z"
  }
}
```

**Important Notes:**
- The response includes `id` at the root level AND inside `data` for frontend compatibility
- Frontend should use: `response.data.id` or `response.id` (both work)
- This calculation ID will be used when calling `POST /api/bnpl/apply`
- Minimum loan amount: ₦1,500,000 (validated on backend)

**Error Response (422 - Amount Too Low):**
```json
{
  "status": "error",
  "message": "Your order total does not meet the minimum ₦1,500,000 amount required for credit financing. To qualify for Buy Now, Pay Later, please add more items to your cart. Thank you."
}
```

**Error Response (422 - Pending Loan Exists):**
```json
{
  "status": "error",
  "message": "You already have a pending loan request. Please wait until it is processed."
}
```

---

### 2. Get Bundles (Buy Now Flow - Step 3.5)

**Endpoint:** `GET /api/bundles`

**Purpose:** Fetch available solar system bundles for selection in Buy Now flow.

**Headers:**
```
Accept: application/json
Authorization: Bearer {access_token} (Optional - route is public)
```

**Query Parameters:** None

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "title": "5KVA Solar System Bundle",
      "bundle_type": "Residential",
      "featured_image": "https://example.com/images/bundles/5kva-bundle.jpg",
      "total_price": 2800000.00,
      "discount_price": 2500000.00,
      "description": "Complete 5KVA solar system with inverter, batteries, and panels",
      "is_active": true,
      "created_at": "2025-11-26T10:00:00.000000Z",
      "updated_at": "2025-11-26T10:00:00.000000Z"
    },
    {
      "id": 2,
      "title": "10KVA Solar System Bundle",
      "bundle_type": "Commercial",
      "featured_image": "https://example.com/images/bundles/10kva-bundle.jpg",
      "total_price": 5000000.00,
      "discount_price": 4500000.00,
      "description": "Complete 10KVA solar system",
      "is_active": true,
      "created_at": "2025-11-26T10:00:00.000000Z",
      "updated_at": "2025-11-26T10:00:00.000000Z"
    }
  ],
  "message": "Bundles retrieved successfully"
}
```

**Field Descriptions:**
- `id` (integer, required): Bundle ID (use for `bundle_id` in checkout)
- `title` (string, required): Bundle name/title
- `bundle_type` (string, optional): Type of bundle (e.g., "Residential", "Commercial")
- `featured_image` (string, optional): Full image URL
- `total_price` (number, required): Original price
- `discount_price` (number, optional): Discounted price (if available, use this for checkout)
- `description` (string, optional): Bundle description
- `is_active` (boolean): Whether bundle is available

**Frontend Handling:**
- The response is always in format: `{status: "success", data: [...], message: "..."}`
- Access bundles via: `response.data` (always an array)
- Use `discount_price` if available, otherwise use `total_price`

---

### 3. Checkout & Generate Invoice

**Endpoint:** `POST /api/orders/checkout`

**Purpose:** Generate invoice/order for Buy Now purchases AND audit orders (BNPL flow).

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
Accept: application/json
```

#### A. Buy Now Checkout

**Request Body:**
```json
{
  "customer_type": "residential",
  "product_category": "full-kit",
  "installer_choice": "troosolar",
  "include_insurance": true,
  "amount": 2500000,
  "bundle_id": 123,
  "state_id": 1,
  "delivery_location_id": 1,
  "add_ons": [1, 2]
}
```

**OR with product_id:**
```json
{
  "product_id": 456,
  "installer_choice": "own",
  "include_insurance": false,
  "amount": 2000000
}
```

**Field Descriptions:**
- `product_id` (integer, optional): Product ID (use if buying single product)
- `bundle_id` (integer, optional): Bundle ID (use if buying bundle)
- `amount` (number, optional): Direct amount (use if no product/bundle ID)
- `customer_type` (string, optional): `residential` | `sme` | `commercial`
- `product_category` (string, optional): `full-kit` | `inverter-battery` | `battery-only` | etc.
- `installer_choice` (string, **required for Buy Now**): `troosolar` | `own`
- `include_insurance` (boolean, optional): Include insurance (0.5% of product price)
- `include_inspection` (boolean, optional): Include inspection fee
- `state_id` (integer, optional): State ID for dynamic delivery fee
- `delivery_location_id` (integer, optional): Delivery location ID
- `add_ons` (array, optional): Array of add-on IDs

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Invoice calculated successfully",
  "data": {
    "order_id": 789,
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
    "total": 2647500.00,
    "order_type": "buy_now",
    "installer_choice": "troosolar",
    "note": "Installation fees may change after site inspection. Any difference will be updated and shared with you for a one-off payment before installation."
  }
}
```

#### B. Audit Order Checkout (BNPL Flow - Step 7)

**Request Body:**
```json
{
  "product_category": "audit",
  "amount": 50000,
  "audit_type": "home-office",
  "customer_type": "residential",
  "property_state": "Lagos",
  "property_address": "123 Main Street",
  "property_floors": 2,
  "property_rooms": 4
}
```

**Field Descriptions:**
- `product_category` (string, **required for audit**): Must be `"audit"`
- `amount` (number, required): Audit fee amount
- `audit_type` (string, optional): `home-office` | `commercial`
- `customer_type` (string, optional): `residential` | `sme` | `commercial`
- `property_state` (string, optional): State name
- `property_address` (string, optional): Property address
- `property_floors` (integer, optional): Number of floors
- `property_rooms` (integer, optional): Number of rooms

**Note:** `installer_choice` is NOT required for audit orders.

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Audit order created successfully",
  "data": {
    "order_id": 12345,
    "audit_fee": 50000.00,
    "total": 50000.00,
    "order_type": "audit",
    "audit_type": "home-office",
    "created_at": "2025-11-26T10:30:00.000000Z"
  }
}
```

**Error Response (422):**
```json
{
  "status": "error",
  "message": "Either product_id, bundle_id, or amount is required. Please provide one of them in your request."
}
```

---

### 4. Payment Confirmation

**Endpoint:** `POST /api/order/payment-confirmation`

**Purpose:** Confirm payment for orders (Buy Now, BNPL Audit, Wallet payments).

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "amount": "50000",
  "orderId": 12345,
  "txId": "FLW1234567890",
  "type": "direct"
}
```

**Field Descriptions:**
- `amount` (string, required): Payment amount as string
- `orderId` (integer, required): Order ID from checkout response
- `txId` (string, required): Transaction ID from payment gateway (Flutterwave)
- `type` (string, required): Payment type
  - `"direct"` - Direct payment (Buy Now)
  - `"audit"` - Audit payment (BNPL flow)
  - `"wallet"` - Wallet payment

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "payment confirmed",
  "data": {
    "order_id": 12345,
    "payment_status": "confirmed",
    "transaction_id": "FLW1234567890",
    "amount": 50000.00,
    "type": "direct",
    "confirmed_at": "2025-11-26T10:30:00.000000Z",
    "transaction": {
      "id": 789,
      "user_id": 2,
      "amount": 50000,
      "tx_id": "FLW1234567890",
      "title": "Order Payment - Direct",
      "type": "outgoing",
      "method": "Direct",
      "status": "Completed",
      "transacted_at": "2025-11-26T10:30:00.000000Z"
    }
  }
}
```

**Business Logic:**
- For `type="audit"`: Marks audit order as paid, enables calendar booking (48 hours after payment)
- For `type="direct"`: Marks Buy Now order as paid, enables installation calendar (72 hours after payment)
- For `type="wallet"`: Deducts from user's loan wallet balance

**Error Response (422 - Validation):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "type": ["The selected type is invalid."],
    "orderId": ["The order id field is required."]
  }
}
```

**Error Response (404):**
```json
{
  "status": "error",
  "message": "order does not found"
}
```

---

### 5. Calendar Slots

**Endpoint:** `GET /api/calendar/slots`

**Purpose:** Get available time slots for audit or installation scheduling.

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
```

**Query Parameters:**
- `type` (string, required): `audit` | `installation`
- `payment_date` (string, required): Date in `YYYY-MM-DD` format

**Example Request:**
```http
GET /api/calendar/slots?type=installation&payment_date=2025-12-01
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "slots": [
      {
        "date": "2025-12-04",
        "time": "09:00",
        "available": true
      },
      {
        "date": "2025-12-04",
        "time": "13:00",
        "available": true
      },
      {
        "date": "2025-12-05",
        "time": "09:00",
        "available": false
      }
    ],
    "earliest_date": "2025-12-04",
    "message": "Installation slots available starting 72 hours after payment"
  },
  "message": "Available slots fetched successfully"
}
```

**Business Logic:**
- For `type=installation` (Buy Now): Slots start **72 hours (3 days)** after `payment_date`
- For `type=audit` (BNPL): Slots start **48 hours (2 days)** after `payment_date`
- Example: If `payment_date = 2025-12-01`:
  - Installation earliest: `2025-12-04`
  - Audit earliest: `2025-12-03`

---

### 6. BNPL Application

**Endpoint:** `POST /api/bnpl/apply`

**Purpose:** Submit BNPL loan application with personal details, property details, and documents.

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: multipart/form-data
Accept: application/json
```

**Request Body (FormData):**

**Basic Fields:**
- `customer_type` (string, required): `residential` | `sme` | `commercial`
- `product_category` (string, required): `full-kit` | `inverter-battery` | `battery-only` | etc.
- `loan_amount` (number, required): Total loan amount (from loan calculation)
- `repayment_duration` (number, required): Tenor in months (3, 6, 9, or 12)
- `credit_check_method` (string, required): `auto` | `manual`

**Personal Details (Nested Array):**
- `personal_details[full_name]` (string, required)
- `personal_details[bvn]` (string, required) - Any 11 characters (no validation)
- `personal_details[phone]` (string, required)
- `personal_details[email]` (string, required, valid email)
- `personal_details[social_media]` (string, **required**) - Social media handle (Facebook/Instagram)

**Property Details (Nested Array):**
- `property_details[state]` (string, required)
- `property_details[address]` (string, required)
- `property_details[landmark]` (string, optional)
- `property_details[floors]` (number, optional)
- `property_details[rooms]` (number, optional)
- `property_details[is_gated_estate]` (boolean, required): `1` | `0`
- `property_details[estate_name]` (string, required if `is_gated_estate === true`)
- `property_details[estate_address]` (string, required if `is_gated_estate === true`)

**Files:**
- `bank_statement` (file, required): PDF, JPG, or PNG (max size: 10MB)
- `live_photo` (file, required): JPG or PNG (max size: 5MB)

**Example FormData (JavaScript):**
```javascript
const formData = new FormData();
formData.append('customer_type', 'residential');
formData.append('product_category', 'full-kit');
formData.append('loan_amount', '2750000');
formData.append('repayment_duration', '12');
formData.append('credit_check_method', 'auto');
formData.append('personal_details[full_name]', 'John Doe');
formData.append('personal_details[bvn]', '12345678901');
formData.append('personal_details[phone]', '08012345678');
formData.append('personal_details[email]', 'john@example.com');
formData.append('personal_details[social_media]', '@johndoe');
formData.append('property_details[state]', 'Lagos');
formData.append('property_details[address]', '123 Main Street');
formData.append('property_details[is_gated_estate]', '0');
formData.append('bank_statement', bankStatementFile);
formData.append('live_photo', livePhotoFile);
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "BNPL application submitted successfully. You will receive feedback within 24-48 hours.",
  "data": {
    "loan_application": {
      "id": 123,
      "user_id": 2,
      "customer_type": "residential",
      "product_category": "full-kit",
      "loan_amount": 2750000.00,
      "repayment_duration": 12,
      "status": "pending",
      "credit_check_method": "auto",
      "property_state": "Lagos",
      "property_address": "123 Main Street",
      "property_landmark": "Near Market",
      "property_floors": 2,
      "property_rooms": 4,
      "is_gated_estate": false,
      "bank_statement_path": "loan_applications/bank_statement_123.pdf",
      "live_photo_path": "loan_applications/live_photo_123.jpg",
      "social_media_handle": "@johndoe",
      "created_at": "2025-11-26T10:30:00.000000Z",
      "updated_at": "2025-11-26T10:30:00.000000Z"
    }
  }
}
```

**Error Response (422 - Validation):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "personal_details.social_media": ["The personal details.social media field is required."],
    "loan_amount": ["The loan amount must be at least 1500000."]
  }
}
```

**Important Notes:**
- Social media handle is **COMPULSORY** - application will be rejected without it
- Minimum loan amount: ₦1,500,000
- BVN can be any 11 characters (no digits-only restriction)
- Files are stored and paths returned in response

---

### 7. Get BNPL Application Status

**Endpoint:** `GET /api/bnpl/status/{application_id}`

**Purpose:** Check the status of a BNPL application.

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "loan_application": {
      "id": 123,
      "status": "approved",
      "loan_amount": 2750000.00,
      "repayment_duration": 12,
      "created_at": "2025-11-26T10:30:00.000000Z",
      "updated_at": "2025-11-26T14:20:00.000000Z"
    },
    "guarantor_required": true,
    "next_step": "guarantor_form"
  },
  "message": "Application status retrieved successfully"
}
```

**Status Values:**
- `pending` - Application under review (24-48 hours)
- `approved` - Application approved, proceed to guarantor
- `rejected` - Application rejected
- `counter_offer` - Counter offer available

---

### 8. Invite/Save Guarantor

**Endpoint:** `POST /api/bnpl/guarantor/invite`

**Purpose:** Save guarantor details and optionally send invitation.

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "loan_application_id": 123,
  "full_name": "Jane Doe",
  "phone": "08098765432",
  "email": "jane@example.com",
  "relationship": "Spouse"
}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Guarantor details saved successfully",
  "data": {
    "id": 456,
    "loan_application_id": 123,
    "full_name": "Jane Doe",
    "phone": "08098765432",
    "email": "jane@example.com",
    "relationship": "Spouse",
    "status": "pending",
    "created_at": "2025-11-26T15:00:00.000000Z"
  }
}
```

---

### 9. Upload Guarantor Form

**Endpoint:** `POST /api/bnpl/guarantor/upload`

**Purpose:** Upload signed guarantor form.

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: multipart/form-data
Accept: application/json
```

**Request Body (FormData):**
- `guarantor_id` (integer, required): ID from guarantor invite response
- `signed_form` (file, required): PDF, JPG, or PNG (max size: 10MB)

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Guarantor form uploaded successfully",
  "data": {
    "id": 456,
    "signed_form_path": "guarantors/signed_form_456.pdf",
    "status": "pending",
    "updated_at": "2025-11-26T15:30:00.000000Z"
  }
}
```

---

## 🔄 Integration Flow Examples

### Buy Now Flow Integration

```javascript
// Step 1: Get bundles (optional - if user selects bundle)
const bundlesResponse = await fetch('/api/bundles', {
  headers: { 'Authorization': `Bearer ${token}` }
});
const bundles = bundlesResponse.data;

// Step 2: Checkout
const checkoutResponse = await fetch('/api/orders/checkout', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    bundle_id: selectedBundleId,
    installer_choice: 'troosolar',
    include_insurance: true,
    amount: 2500000
  })
});
const { order_id, total } = checkoutResponse.data;

// Step 3: Payment (Flutterwave integration)
// ... payment gateway integration ...

// Step 4: Payment Confirmation
const paymentResponse = await fetch('/api/order/payment-confirmation', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    amount: total.toString(),
    orderId: order_id,
    txId: flutterwaveTransactionId,
    type: 'direct'
  })
});

// Step 5: Get Calendar Slots (72 hours after payment)
const paymentDate = new Date().toISOString().split('T')[0];
const slotsResponse = await fetch(
  `/api/calendar/slots?type=installation&payment_date=${paymentDate}`,
  {
    headers: { 'Authorization': `Bearer ${token}` }
  }
);
const slots = slotsResponse.data.slots;
```

### BNPL Flow Integration

```javascript
// Step 1: Loan Calculation (BEFORE application)
const loanCalcResponse = await fetch('/api/loan-calculation', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    product_amount: 2500000,
    loan_amount: 2750000,
    repayment_duration: 12
  })
});

// Get calculation ID (handle both response formats)
const calculationId = loanCalcResponse.data?.id || loanCalcResponse.id;

// Step 2: Submit BNPL Application
const formData = new FormData();
formData.append('customer_type', 'residential');
formData.append('product_category', 'full-kit');
formData.append('loan_amount', '2750000');
formData.append('repayment_duration', '12');
formData.append('credit_check_method', 'auto');
formData.append('personal_details[full_name]', 'John Doe');
formData.append('personal_details[bvn]', '12345678901');
formData.append('personal_details[phone]', '08012345678');
formData.append('personal_details[email]', 'john@example.com');
formData.append('personal_details[social_media]', '@johndoe'); // REQUIRED
formData.append('property_details[state]', 'Lagos');
formData.append('property_details[address]', '123 Main Street');
formData.append('property_details[is_gated_estate]', '0');
formData.append('bank_statement', bankStatementFile);
formData.append('live_photo', livePhotoFile);

const applyResponse = await fetch('/api/bnpl/apply', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
    // Don't set Content-Type for FormData - browser sets it automatically
  },
  body: formData
});

const applicationId = applyResponse.data.loan_application.id;

// Step 3: Check Status (polling or manual refresh)
const statusResponse = await fetch(`/api/bnpl/status/${applicationId}`, {
  headers: { 'Authorization': `Bearer ${token}` }
});

if (statusResponse.data.loan_application.status === 'approved') {
  // Step 4: Save Guarantor
  const guarantorResponse = await fetch('/api/bnpl/guarantor/invite', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      loan_application_id: applicationId,
      full_name: 'Jane Doe',
      phone: '08098765432',
      email: 'jane@example.com',
      relationship: 'Spouse'
    })
  });

  const guarantorId = guarantorResponse.data.id;

  // Step 5: Upload Signed Form
  const uploadFormData = new FormData();
  uploadFormData.append('guarantor_id', guarantorId);
  uploadFormData.append('signed_form', signedFormFile);

  await fetch('/api/bnpl/guarantor/upload', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: uploadFormData
  });
}
```

### Audit Order Integration (BNPL Flow - Step 7)

```javascript
// Step 1: Create Audit Order
const auditCheckoutResponse = await fetch('/api/orders/checkout', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    product_category: 'audit',
    amount: 50000,
    audit_type: 'home-office',
    customer_type: 'residential',
    property_state: 'Lagos',
    property_address: '123 Main Street',
    property_floors: 2,
    property_rooms: 4
  })
});

const auditOrderId = auditCheckoutResponse.data.order_id;

// Step 2: Payment (Flutterwave)
// ... payment gateway integration ...

// Step 3: Payment Confirmation
await fetch('/api/order/payment-confirmation', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    amount: '50000',
    orderId: auditOrderId,
    txId: flutterwaveTransactionId,
    type: 'audit' // Important: use 'audit' type
  })
});

// Step 4: Get Calendar Slots (48 hours after payment)
const paymentDate = new Date().toISOString().split('T')[0];
const auditSlotsResponse = await fetch(
  `/api/calendar/slots?type=audit&payment_date=${paymentDate}`,
  {
    headers: { 'Authorization': `Bearer ${token}` }
  }
);
```

---

## 📦 Response Format Handling

### Standard Response Format

All endpoints return responses in this format:

**Success:**
```json
{
  "status": "success",
  "data": { ... },
  "message": "Operation completed successfully"
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

### Handling Multiple Response Formats

Some endpoints may return data in different formats. Frontend should handle:

**Loan Calculation:**
```javascript
// Handle both formats
const calculationId = response.data?.id || response.id;
```

**Bundles:**
```javascript
// Always access via response.data (always an array)
const bundles = response.data; // Array of bundles
```

---

## ⚠️ Error Handling

### Status Codes

- `200 OK` - Request successful
- `401 Unauthorized` - Missing or invalid token
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation error
- `500 Internal Server Error` - Server error

### Common Error Responses

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated."
}
```

**422 Validation Error:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "field": ["Error message"]
  }
}
```

**404 Not Found:**
```json
{
  "status": "error",
  "message": "Resource not found"
}
```

---

## ✅ Testing Checklist

### Buy Now Flow
- [ ] Get bundles list
- [ ] Checkout with product_id
- [ ] Checkout with bundle_id
- [ ] Checkout with amount
- [ ] Payment confirmation (type: direct)
- [ ] Get installation calendar slots (72 hours after payment)

### BNPL Flow
- [ ] Create loan calculation
- [ ] Submit BNPL application (with all required fields)
- [ ] Check application status
- [ ] Save guarantor details
- [ ] Upload guarantor form
- [ ] Create audit order
- [ ] Payment confirmation (type: audit)
- [ ] Get audit calendar slots (48 hours after payment)

### Validation Tests
- [ ] Minimum loan amount (₦1,500,000)
- [ ] Social media handle required
- [ ] Gated estate validation (estate name/address required if Yes)
- [ ] File upload validation (size, format)
- [ ] BVN format (any 11 characters)

---

## 🔗 Quick Reference

### Route Summary

**Public Routes:**
```
GET  /api/config/customer-types
GET  /api/config/audit-types
GET  /api/config/states
GET  /api/bundles
```

**Protected Routes:**
```
POST /api/loan-calculation
POST /api/orders/checkout
POST /api/order/payment-confirmation
GET  /api/calendar/slots
POST /api/bnpl/apply
GET  /api/bnpl/status/{id}
POST /api/bnpl/guarantor/invite
POST /api/bnpl/guarantor/upload
POST /api/bnpl/counteroffer/accept
```

---

## 📝 Important Notes

1. **Loan Calculation MUST be called BEFORE BNPL application**
2. **Social Media Handle is COMPULSORY** for BNPL applications
3. **BVN can be any 11 characters** (no digits-only restriction)
4. **Audit orders** use `product_category: "audit"` in checkout
5. **Payment confirmation** uses `type: "audit"` for audit payments
6. **Calendar slots** are available 48h (audit) or 72h (installation) after payment
7. **Minimum loan amount**: ₦1,500,000 (configurable in backend)
8. **Repayment duration**: Only 3, 6, 9, or 12 months allowed

---

## 🚀 Ready for Integration

All routes are implemented and ready for frontend integration. Use this guide as your reference for:

- Request/response formats
- Field requirements
- Error handling
- Integration flow examples
- Testing checklist

**Last Updated:** November 26, 2025

