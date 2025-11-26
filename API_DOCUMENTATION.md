# TrooSolar Backend API Documentation

## New Endpoints for Buy Now & BNPL Flows

This document outlines all the new API endpoints added for the Buy Now and Buy Now, Pay Later (BNPL) functionality.

---

## Table of Contents

1. [Configuration Endpoints (Public)](#configuration-endpoints-public)
2. [BNPL Flow Endpoints (Protected)](#bnpl-flow-endpoints-protected)
3. [Buy Now Flow Endpoints (Protected)](#buy-now-flow-endpoints-protected)
4. [Calendar/Scheduling Endpoints (Protected)](#calendarscheduling-endpoints-protected)
5. [Updated Endpoints](#updated-endpoints)

---

## Configuration Endpoints (Public)

These endpoints are **public** and do not require authentication. Add them **outside** the `auth:sanctum` middleware group.

### 1. Get Customer Types

**Endpoint:** `GET /api/config/customer-types`

**Description:** Returns available customer types for BNPL/Buy Now flow selection.

**Request:**
```http
GET /api/config/customer-types
```

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": "residential",
      "label": "For Residential"
    },
    {
      "id": "sme",
      "label": "For SMEs"
    },
    {
      "id": "commercial",
      "label": "For Commercial and Industrial"
    }
  ],
  "message": "Customer types retrieved successfully"
}
```

**Status Code:** `200 OK`

---

### 2. Get Audit Types

**Endpoint:** `GET /api/config/audit-types`

**Description:** Returns available audit types for professional audit flow.

**Request:**
```http
GET /api/config/audit-types
```

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": "home-office",
      "label": "Home / Office"
    },
    {
      "id": "commercial",
      "label": "Commercial / Industrial"
    }
  ],
  "message": "Audit types retrieved successfully"
}
```

**Status Code:** `200 OK`

---

## BNPL Flow Endpoints (Protected)

These endpoints require authentication. Add them **inside** the `auth:sanctum` middleware group.

### 1. Submit BNPL Application

**Endpoint:** `POST /api/bnpl/apply`

**Description:** Submit a BNPL loan application with personal details, property details, and required documents.

**Authentication:** Required (Bearer Token)

**Request:**
```http
POST /api/bnpl/apply
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

**Request Body (Form Data):**
```javascript
{
  "customer_type": "residential",              // required: "residential" | "sme" | "commercial"
  "product_category": "full-kit",               // optional
  "loan_amount": 2500000,                       // required, minimum: 1500000 (₦1.5M)
  "repayment_duration": 6,                      // required: 3, 6, 9, or 12 months
  "credit_check_method": "auto",                // optional: "auto" | "manual"
  
  // Personal Details (JSON string or separate fields)
  "personal_details": {
    "full_name": "John Doe",
    "bvn": "12345678901",
    "phone": "08012345678",
    "social_media": "@johndoe"
  },
  
  // Property Details (JSON string or separate fields)
  "property_details": {
    "state": "Lagos",
    "address": "123 Main Street",
    "landmark": "Near City Mall",
    "floors": 2,
    "rooms": 4,
    "is_gated_estate": true,
    "estate_name": "Sunshine Estate",
    "estate_address": "Sunshine Estate, Lagos"
  },
  
  // Files
  "bank_statement": <file>,                    // optional: PDF, JPG, PNG (max 5MB)
  "live_photo": <file>                         // optional: JPG, PNG (max 5MB)
}
```

**Alternative (JSON with base64 files):**
```json
{
  "customer_type": "residential",
  "product_category": "full-kit",
  "loan_amount": 2500000,
  "repayment_duration": 6,
  "credit_check_method": "auto",
  "personal_details": {
    "full_name": "John Doe",
    "bvn": "12345678901",
    "phone": "08012345678",
    "social_media": "@johndoe"
  },
  "property_details": {
    "state": "Lagos",
    "address": "123 Main Street",
    "landmark": "Near City Mall",
    "floors": 2,
    "rooms": 4,
    "is_gated_estate": true,
    "estate_name": "Sunshine Estate",
    "estate_address": "Sunshine Estate, Lagos"
  }
}
```

**Success Response:**
```json
{
  "status": "success",
  "data": {
    "loan_application": {
      "id": 1,
      "user_id": 5,
      "loan_amount": 2500000,
      "repayment_duration": 6,
      "customer_type": "residential",
      "status": "pending",
      "created_at": "2025-11-26T10:30:00.000000Z",
      "updated_at": "2025-11-26T10:30:00.000000Z"
    },
    "message": "BNPL application submitted successfully. You will receive feedback within 24-48 hours."
  },
  "message": "BNPL application submitted successfully"
}
```

**Error Response (Minimum Amount Not Met):**
```json
{
  "status": "error",
  "message": "Your order total does not meet the minimum ₦1,500,000 amount required for credit financing. To qualify for Buy Now, Pay Later, please add more items to your cart. Thank you."
}
```
**Status Code:** `422 Unprocessable Entity`

**Error Response (Loan Calculation Not Found):**
```json
{
  "status": "error",
  "message": "Loan calculation not found. Please calculate your loan first."
}
```
**Status Code:** `404 Not Found`

---

### 2. Get BNPL Application Status

**Endpoint:** `GET /api/bnpl/status/{application_id}`

**Description:** Get the current status of a BNPL application.

**Authentication:** Required (Bearer Token)

**Request:**
```http
GET /api/bnpl/status/1
Authorization: Bearer {token}
```

**Success Response:**
```json
{
  "status": "success",
  "data": {
    "application_id": 1,
    "status": "pending",              // "pending" | "approved" | "rejected" | "counter_offer"
    "loan_amount": 2500000,
    "repayment_duration": 6
  },
  "message": "Application status retrieved successfully"
}
```

**Error Response:**
```json
{
  "status": "error",
  "message": "Application not found"
}
```
**Status Code:** `404 Not Found`

---

### 3. Invite/Add Guarantor

**Endpoint:** `POST /api/bnpl/guarantor/invite`

**Description:** Add guarantor details to a loan application.

**Authentication:** Required (Bearer Token)

**Request:**
```http
POST /api/bnpl/guarantor/invite
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "loan_application_id": 1,            // required
  "full_name": "Jane Smith",          // required
  "email": "jane@example.com",        // optional
  "phone": "08098765432",              // required
  "bvn": "98765432109",                // optional (11 digits)
  "relationship": "Spouse"              // optional
}
```

**Success Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "user_id": 5,
    "loan_application_id": 1,
    "full_name": "Jane Smith",
    "email": "jane@example.com",
    "phone": "08098765432",
    "bvn": "98765432109",
    "relationship": "Spouse",
    "status": "pending",
    "created_at": "2025-11-26T10:35:00.000000Z",
    "updated_at": "2025-11-26T10:35:00.000000Z"
  },
  "message": "Guarantor details saved successfully"
}
```

**Error Response:**
```json
{
  "status": "error",
  "message": "Loan application not found"
}
```
**Status Code:** `404 Not Found`

---

### 4. Upload Guarantor Form

**Endpoint:** `POST /api/bnpl/guarantor/upload`

**Description:** Upload the signed guarantor form document.

**Authentication:** Required (Bearer Token)

**Request:**
```http
POST /api/bnpl/guarantor/upload
Content-Type: multipart/form-data
Authorization: Bearer {token}
```

**Request Body (Form Data):**
```javascript
{
  "guarantor_id": 1,                   // required
  "signed_form": <file>                // required: PDF, JPG, PNG (max 5MB)
}
```

**Success Response:**
```json
{
  "status": "success",
  "data": {
    "guarantor_id": 1,
    "signed_form_path": "loan_applications/guarantor_form_1_1732620000.pdf"
  },
  "message": "Guarantor form uploaded successfully"
}
```

**Error Response:**
```json
{
  "status": "error",
  "message": "Guarantor not found"
}
```
**Status Code:** `404 Not Found`

---

### 5. Accept Counteroffer

**Endpoint:** `POST /api/bnpl/counteroffer/accept`

**Description:** Accept a counteroffer from admin with new terms (minimum deposit and tenor).

**Authentication:** Required (Bearer Token)

**Request:**
```http
POST /api/bnpl/counteroffer/accept
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "application_id": 1,                 // required
  "minimum_deposit": 750000,           // required (30% of loan amount)
  "minimum_tenor": 9                   // required (minimum 3 months)
}
```

**Success Response:**
```json
{
  "status": "success",
  "data": {
    "application_id": 1,
    "minimum_deposit": 750000,
    "minimum_tenor": 9
  },
  "message": "Counteroffer accepted successfully"
}
```

**Error Response:**
```json
{
  "status": "error",
  "message": "Application not found"
}
```
**Status Code:** `404 Not Found`

---

## Buy Now Flow Endpoints (Protected)

### 1. Calculate Checkout Invoice

**Endpoint:** `POST /api/orders/checkout`

**Description:** Calculate invoice breakdown for Buy Now orders with optional fees.

**Authentication:** Required (Bearer Token)

**Request:**
```http
POST /api/orders/checkout
Content-Type: application/json
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "product_id": 123,                   // optional (if buying single product)
  "bundle_id": 456,                    // optional (if buying bundle)
  "installer_choice": "troosolar",     // required: "troosolar" | "own"
  "include_insurance": true,           // optional: boolean (default: false)
  "include_inspection": true            // optional: boolean (default: false)
}
```

**Note:** Either `product_id` OR `bundle_id` must be provided, not both.

**Success Response:**
```json
{
  "status": "success",
  "data": {
    "product_price": 2000000,
    "installation_fee": 50000,         // Only if installer_choice = "troosolar"
    "material_cost": 30000,            // Only if installer_choice = "troosolar"
    "delivery_fee": 25000,
    "inspection_fee": 15000,           // Only if include_inspection = true
    "insurance_fee": 10000,            // Only if include_insurance = true (0.5% of product_price)
    "total": 2135000,
    "installer_choice": "troosolar",
    "note": "Installation fees may change after site inspection. Any difference will be updated and shared with you for a one-off payment before installation."
  },
  "message": "Invoice calculated successfully"
}
```

**Example Response (No Installation):**
```json
{
  "status": "success",
  "data": {
    "product_price": 2000000,
    "installation_fee": 0,
    "material_cost": 0,
    "delivery_fee": 25000,
    "inspection_fee": 0,
    "insurance_fee": 10000,
    "total": 2035000,
    "installer_choice": "own",
    "note": null
  },
  "message": "Invoice calculated successfully"
}
```

**Error Response:**
```json
{
  "status": "error",
  "message": "Either product_id or bundle_id is required"
}
```
**Status Code:** `422 Unprocessable Entity`

---

## Calendar/Scheduling Endpoints (Protected)

### 1. Get Available Calendar Slots

**Endpoint:** `GET /api/calendar/slots`

**Description:** Get available time slots for audit or installation booking. Returns slots starting 48 hours after payment for audit, or 72 hours for installation.

**Authentication:** Required (Bearer Token)

**Request:**
```http
GET /api/calendar/slots?type=audit&payment_date=2025-11-26
Authorization: Bearer {token}
```

**Query Parameters:**
- `type` (required): `"audit"` or `"installation"`
- `payment_date` (required): Date in `YYYY-MM-DD` format

**Success Response:**
```json
{
  "status": "success",
  "data": {
    "type": "audit",
    "payment_date": "2025-11-26",
    "start_date": "2025-11-28 09:00:00",    // 48 hours after payment for audit
    "slots": [
      {
        "date": "2025-11-28",
        "time": "09:00",
        "datetime": "2025-11-28 09:00:00",
        "available": true
      },
      {
        "date": "2025-11-28",
        "time": "10:00",
        "datetime": "2025-11-28 10:00:00",
        "available": true
      },
      {
        "date": "2025-11-28",
        "time": "11:00",
        "datetime": "2025-11-28 11:00:00",
        "available": true
      }
      // ... more slots for next 30 days (9 AM to 5 PM, hourly)
    ],
    "message": "Available slots starting 48 hours after payment confirmation"
  },
  "message": "Available slots retrieved successfully"
}
```

**Example for Installation (72 hours):**
```http
GET /api/calendar/slots?type=installation&payment_date=2025-11-26
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "type": "installation",
    "payment_date": "2025-11-26",
    "start_date": "2025-11-29 09:00:00",    // 72 hours after payment
    "slots": [
      // ... slots starting from 72 hours after payment
    ],
    "message": "Available slots starting 72 hours after payment confirmation"
  },
  "message": "Available slots retrieved successfully"
}
```

**Error Response (Invalid Type):**
```json
{
  "status": "error",
  "message": "Invalid type. Must be \"audit\" or \"installation\""
}
```
**Status Code:** `422 Unprocessable Entity`

**Error Response (Missing payment_date):**
```json
{
  "status": "error",
  "message": "payment_date is required"
}
```
**Status Code:** `422 Unprocessable Entity`

---

## Updated Endpoints

### Loan Calculator - Minimum Amount Validation

**Endpoint:** `POST /api/loan-calculation`

**Description:** Updated to validate minimum loan amount of ₦1,500,000 for BNPL.

**Error Response (Amount Below Minimum):**
```json
{
  "status": "error",
  "message": "Your order total does not meet the minimum ₦1,500,000 amount required for credit financing. To qualify for Buy Now, Pay Later, please add more items to your cart. Thank you."
}
```
**Status Code:** `422 Unprocessable Entity`

---

## Route Placement Guide

### Public Routes (Add BEFORE `auth:sanctum` middleware)

Add these routes in `routes/api.php` **before** the `Route::middleware('auth:sanctum')->group(function () {` block:

```php
// Configuration endpoints (public)
Route::get('/config/customer-types', [ConfigurationController::class, 'getCustomerTypes']);
Route::get('/config/audit-types', [ConfigurationController::class, 'getAuditTypes']);
```

### Protected Routes (Add INSIDE `auth:sanctum` middleware)

Add these routes **inside** the `Route::middleware('auth:sanctum')->group(function () {` block:

```php
// BNPL Flow endpoints
Route::post('/bnpl/apply', [BNPLController::class, 'apply']);
Route::get('/bnpl/status/{application_id}', [BNPLController::class, 'getStatus']);
Route::post('/bnpl/guarantor/invite', [BNPLController::class, 'inviteGuarantor']);
Route::post('/bnpl/guarantor/upload', [BNPLController::class, 'uploadGuarantorForm']);
Route::post('/bnpl/counteroffer/accept', [BNPLController::class, 'acceptCounterOffer']);

// Buy Now Flow endpoints
Route::post('/orders/checkout', [OrderController::class, 'checkout']);

// Calendar/Scheduling endpoints
Route::get('/calendar/slots', [CalendarController::class, 'getSlots']);
```

---

## Frontend Integration Notes

### 1. Authentication
All protected endpoints require a Bearer token in the Authorization header:
```javascript
headers: {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'application/json'
}
```

### 2. File Uploads
For endpoints that accept files (`/bnpl/apply`, `/bnpl/guarantor/upload`), use `multipart/form-data`:
```javascript
const formData = new FormData();
formData.append('bank_statement', file);
formData.append('loan_amount', 2500000);
// ... other fields

fetch('/api/bnpl/apply', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`
    // Don't set Content-Type for FormData, browser will set it automatically
  },
  body: formData
});
```

### 3. Error Handling
All endpoints return errors in this format:
```json
{
  "status": "error",
  "message": "Error message here"
}
```

Check the `status` field to determine success or error:
- `"success"` = Request successful
- `"error"` = Request failed

### 4. Minimum Loan Amount
The minimum loan amount for BNPL is **₦1,500,000**. Validate this on the frontend before allowing users to proceed with BNPL application.

### 5. Calendar Slots
- **Audit slots**: Available 48 hours after payment confirmation
- **Installation slots**: Available 72 hours after payment confirmation
- Slots are generated for the next 30 days (excluding weekends)
- Time slots: 9:00 AM to 5:00 PM (hourly)

### 6. Invoice Calculation
For Buy Now orders:
- **Installation fee** and **Material cost**: Only included if `installer_choice = "troosolar"`
- **Insurance fee**: Optional (0.5% of product price) if `include_insurance = true`
- **Inspection fee**: Optional if `include_inspection = true`
- **Delivery fee**: Always included

---

## Testing Examples

### Test Configuration Endpoints (No Auth Required)
```bash
curl -X GET http://127.0.0.1:8000/api/config/customer-types
curl -X GET http://127.0.0.1:8000/api/config/audit-types
```

### Test BNPL Application (Auth Required)
```bash
curl -X POST http://127.0.0.1:8000/api/bnpl/apply \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_type": "residential",
    "loan_amount": 2500000,
    "repayment_duration": 6,
    "personal_details": {
      "full_name": "John Doe",
      "phone": "08012345678"
    },
    "property_details": {
      "state": "Lagos",
      "address": "123 Main Street"
    }
  }'
```

### Test Calendar Slots (Auth Required)
```bash
curl -X GET "http://127.0.0.1:8000/api/calendar/slots?type=audit&payment_date=2025-11-26" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Status Codes Reference

- `200 OK` - Request successful
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation error or business rule violation
- `500 Internal Server Error` - Server error

---

## Support

For questions or issues, contact the backend team or refer to the main API documentation.

**Last Updated:** November 26, 2025

