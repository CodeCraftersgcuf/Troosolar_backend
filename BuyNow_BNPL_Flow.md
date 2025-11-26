# TrooSolar - Buy Now & BNPL Complete Flow Guide

This document provides a comprehensive guide for backend developers implementing the **Buy Now** and **Buy Now Pay Later (BNPL)** flows. It includes all API endpoints, data structures, user journey steps, and business logic requirements.

---

## 📋 Table of Contents

1. [Buy Now Flow](#buy-now-flow)
2. [BNPL Flow](#bnpl-flow)
3. [Shared Components & Logic](#shared-components--logic)
4. [Database Schema Requirements](#database-schema-requirements)
5. [API Endpoints Reference](#api-endpoints-reference)
6. [Business Rules & Validation](#business-rules--validation)

---

## 🛒 Buy Now Flow

### Overview
The Buy Now flow allows customers to purchase solar systems outright with immediate payment. The flow consists of **5 main steps**.

### User Journey

#### **Step 1: Customer Type Selection**
**UI:** User selects who they're purchasing for
- **Options:**
  - `residential` - For Residential
  - `sme` - For SMEs
  - `commercial` - Commercial & Industrial

**Frontend Action:** Stores `customerType` in form state, proceeds to Step 2

**No API Call Required** - Static options

---

#### **Step 2: Product Category Selection**
**UI:** User selects product category
- **Options:**
  - `full-kit` - Solar Panels, Inverter & Battery
  - `inverter-battery` - Inverter & Battery Solution
  - `battery-only` - Battery Only
  - `inverter-only` - Inverter Only
  - `panels-only` - Solar Panels Only

**Frontend Logic:**
- If category is `full-kit` or `inverter-battery` → Go to Step 3 (Method Selection)
- If category is individual component (`battery-only`, `inverter-only`, `panels-only`) → Skip to Step 4 (Checkout Options)
  - Sets mock price based on category:
    - `battery-only`: ₦800,000
    - `inverter-only`: ₦500,000
    - `panels-only`: ₦200,000

**No API Call Required** - Static options

---

#### **Step 3: Method Selection** (Only for `full-kit` or `inverter-battery`)
**UI:** User chooses how to proceed
- **Options:**
  - `choose-system` - Choose my solar system (from catalog)
  - `build-system` - Build My System (custom configuration)
  - `audit` - Request Professional Audit

**Frontend Logic:**
- If `choose-system` → Sets mock price ₦2,500,000 → Go to Step 4
- If `audit` → Redirects to BNPL Audit flow (alerts user)
- If `build-system` → Shows "Under construction" alert

**No API Call Required** - Static options

---

#### **Step 4: Checkout Options**
**UI:** User configures installation and insurance preferences

**Form Fields:**
- `installer_choice` (Required):
  - `troosolar` - Use TrooSolar Certified Installer (includes 1-Year Installation Warranty)
  - `own` - Use My Own Installer (no warranty)
- `include_insurance` (Optional, Boolean):
  - `true` - Include Insurance (0.5% of product price)
  - `false` - No Insurance

**Frontend Action:** On "Proceed to Invoice" button click → Calls checkout API

---

#### **Step 5: Invoice & Payment**
**UI:** Displays invoice breakdown and payment options

**Invoice Breakdown:**
- Product Price
- Installation Fee (if `installer_choice === 'troosolar'`)
- Delivery Fee
- Insurance Fee (if `include_insurance === true`)
- **Total**

**API Calls:**
1. **Checkout API** (Step 4 → Step 5)
2. **Calendar Slots API** (Fetched when Step 5 loads)

---

### Buy Now API Endpoints

#### **1. Checkout & Generate Invoice**
**Endpoint:** `POST /api/orders/checkout`

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "customer_type": "residential",
  "product_category": "full-kit",
  "installer_choice": "troosolar",
  "include_insurance": true,
  "amount": 2500000
}
```

**Field Descriptions:**
- `customer_type` (string, required): `residential` | `sme` | `commercial`
- `product_category` (string, required): `full-kit` | `inverter-battery` | `battery-only` | `inverter-only` | `panels-only`
- `installer_choice` (string, required): `troosolar` | `own`
- `include_insurance` (boolean, required): `true` | `false`
- `amount` (number, required): Product price in NGN

**Response (Success):**
```json
{
  "status": "success",
  "message": "Invoice generated successfully",
  "data": {
    "order_id": 12345,
    "product_price": 2500000.00,
    "installation_fee": 50000.00,
    "delivery_fee": 25000.00,
    "insurance_fee": 12500.00,
    "material_cost": 50000.00,
    "inspection_fee": 10000.00,
    "total": 2647500.00,
    "order_type": "buy_now"
  }
}
```

**Response Fields:**
- `order_id` (integer): Unique order identifier
- `product_price` (decimal): Base product price
- `installation_fee` (decimal): Installation cost (only if `installer_choice === 'troosolar'`, else 0)
- `delivery_fee` (decimal): Delivery/logistics cost
- `insurance_fee` (decimal): Insurance cost (0.5% of product_price if `include_insurance === true`, else 0)
- `material_cost` (decimal): Additional materials (cables, breakers, etc.)
- `inspection_fee` (decimal): Site inspection fee
- `total` (decimal): Grand total
- `order_type` (string): `buy_now`

**Error Response:**
```json
{
  "status": "error",
  "message": "Failed to process checkout",
  "errors": {
    "installer_choice": ["The installer choice field is required."]
  }
}
```

**Business Logic:**
- Calculate `installation_fee`:
  - If `installer_choice === 'troosolar'` → ₦50,000 (or configurable amount)
  - If `installer_choice === 'own'` → ₦0
- Calculate `insurance_fee`:
  - If `include_insurance === true` → `product_price * 0.005` (0.5%)
  - If `include_insurance === false` → ₦0
- Calculate `delivery_fee`: Fixed or based on location (₦25,000 default)
- Calculate `material_cost`: Fixed or based on product category (₦50,000 default)
- Calculate `inspection_fee`: Fixed (₦10,000 default)
- Create order record in `orders` table with `order_type = 'buy_now'`

---

#### **2. Get Calendar Slots (Installation Scheduling)**
**Endpoint:** `GET /api/calendar/slots`

**Query Parameters:**
- `type` (string, required): `installation` | `audit`
- `payment_date` (string, required): Date in `YYYY-MM-DD` format

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
```

**Example Request:**
```
GET /api/calendar/slots?type=installation&payment_date=2025-12-01
```

**Response (Success):**
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
  }
}
```

**Business Logic:**
- For `type=installation` (Buy Now):
  - Return slots starting **72 hours (3 days)** after `payment_date`
  - Example: If `payment_date = 2025-12-01`, earliest slot = `2025-12-04`
- For `type=audit` (BNPL):
  - Return slots starting **48 hours (2 days)** after `payment_date`
  - Example: If `payment_date = 2025-12-01`, earliest slot = `2025-12-03`
- Exclude weekends/holidays if configured
- Check availability based on existing bookings

---

### Buy Now Data Flow Summary

```
Step 1: Customer Type → Step 2: Product Category → Step 3: Method Selection (if applicable) → Step 4: Checkout Options
  ↓
POST /api/orders/checkout
  ↓
Step 5: Invoice Display
  ↓
GET /api/calendar/slots?type=installation&payment_date={date}
  ↓
User proceeds to payment (external payment gateway)
```

---

## 💳 BNPL (Buy Now Pay Later) Flow

### Overview
The BNPL flow allows customers to purchase solar systems with flexible payment plans. Minimum order value: **₦1,500,000**. The flow consists of **21 steps** with multiple branches.

### User Journey

#### **Step 1: Customer Type Selection**
**UI:** User selects who they're purchasing for

**API Call:** `GET /api/config/customer-types` (on page load)

**Options:**
- `residential` - For Residential
- `sme` - For SMEs
- `commercial` - Commercial & Industrial

**Frontend Action:** Stores `customerType`, proceeds to Step 2

---

#### **Step 2: Product Category Selection**
**UI:** User selects product category

**Options:**
- `full-kit` - Solar Panels, Inverter & Battery
- `inverter-battery` - Inverter & Battery Solution
- `battery-only` - Battery Only
- `inverter-only` - Inverter Only
- `panels-only` - Solar Panels Only

**Frontend Logic:**
- If category is `full-kit` or `inverter-battery` → Go to Step 3 (Method Selection)
- If category is individual component → Skip to Step 8 (Loan Calculator)
  - Sets mock price based on category

---

#### **Step 3: Method Selection** (Only for `full-kit` or `inverter-battery`)
**UI:** User chooses how to proceed

**Options:**
- `choose-system` - Choose my solar system
- `build-system` - Build My System
- `audit` - Request Professional Audit

**Frontend Logic:**
- If `choose-system` → Sets mock price ₦2,500,000 → Go to Step 8 (Loan Calculator)
- If `audit` → Go to Step 4 (Audit Type Selection)
- If `build-system` → Shows "Under construction" alert

---

#### **Step 4: Audit Type Selection** (If `optionType === 'audit'`)
**UI:** User selects audit type

**API Call:** `GET /api/config/audit-types` (on page load)

**Options:**
- `home-office` - Home / Office
- `commercial` - Commercial / Industrial

**Frontend Logic:**
- If `auditType === 'commercial'` → Go to Step 6 (Commercial Notification)
- If `auditType === 'home-office'` → Go to Step 5 (Home/Office Details Form)

---

#### **Step 5: Home/Office Details Form** (If `auditType === 'home-office'`)
**UI:** User fills property details form

**Form Fields:**
- `address` (string, required)
- `state` (string, required)
- `landmark` (string, optional)
- `floors` (number, optional)
- `rooms` (number, optional)
- `isGatedEstate` (boolean)
  - If `true` → Show additional fields:
    - `estateName` (string)
    - `estateAddress` (string)

**Frontend Action:** On submit → Go to Step 7 (Audit Invoice)

**No API Call** - Data stored in form state

---

#### **Step 6: Commercial Notification** (If `auditType === 'commercial'`)
**UI:** Displays notification that commercial audits require manual follow-up

**Frontend Action:** User acknowledges → Admin notification triggered (backend)

**Business Logic:**
- **DO NOT** generate instant invoice for commercial audits
- Trigger notification to admin team
- Admin will contact customer manually

---

#### **Step 7: Audit Invoice** (After Step 5)
**UI:** Displays audit-only invoice

**Invoice Breakdown:**
- Audit Fee: ₦X (configurable)
- Total

**Frontend Action:** User proceeds to payment

**No API Call** - Static invoice (or fetch from backend if available)

---

#### **Step 8: Loan Calculator**
**UI:** User configures loan terms using LoanCalculator component

**Loan Calculator Fields:**
- `depositPercent` (number, range: 30-80%, step: 10%, default: 30%)
- `tenor` (number, options: 3, 6, 9, 12 months, default: 12)
- `interestRate` (number, fixed: 4% monthly)

**Calculations (Frontend):**
- `depositAmount = (totalAmount * depositPercent) / 100`
- `principal = totalAmount - depositAmount`
- `totalInterest = principal * (interestRate / 100) * tenor`
- `totalRepayment = principal + totalInterest`
- `monthlyRepayment = totalRepayment / tenor`

**Validation:**
- Minimum order value: **₦1,500,000**
- If `totalAmount < 1,500,000` → Show error: "Order Value Too Low"

**Frontend Action:** On "Proceed with Plan" → Store loan details → Go to Step 10 (Credit Check Method)

**No API Call** - Calculations done on frontend

---

#### **Step 10: Credit Check Method Selection**
**UI:** User selects credit verification method

**Options:**
- `auto` - Automatic (BVN verification)
- `manual` - Manual (Bank statement review)

**Frontend Action:** Stores `creditCheckMethod`, proceeds to Step 11

**No API Call** - Static options

---

#### **Step 11: Final Application Form**
**UI:** Comprehensive application form with personal and property details

**Form Sections:**

**Personal Details:**
- `fullName` (string, required)
- `bvn` (string, required, 11 digits)
- `phone` (string, required)
- `email` (string, required, valid email)
- `socialMedia` (string, optional) - Social media handle

**Property Details:**
- `state` (string, required)
- `address` (string, required)
- `landmark` (string, optional)
- `floors` (number, optional)
- `rooms` (number, optional)
- `isGatedEstate` (boolean)
  - If `true`:
    - `estateName` (string, required)
    - `estateAddress` (string, required)

**Required Documents:**
- `bankStatement` (file, required) - PDF, JPG, or PNG (Last 6 months)
- `livePhoto` (file, required) - JPG or PNG (Selfie/Live photo)

**Frontend Action:** On "Submit Application" → Calls `POST /api/bnpl/apply`

---

#### **Step 12: Application Submitted (Pending Status)**
**UI:** Displays "Application Submitted" confirmation

**Status:** `pending`

**Message:** "Your application is under review. This usually takes 24-48 hours."

**Frontend Action:** User can check status again

**API Call:** `GET /api/bnpl/status/{application_id}` (polling or manual refresh)

---

#### **Step 13: Application Approved**
**UI:** Displays approval confirmation

**Status:** `approved`

**Message:** "Loan Approved! Please proceed to download the Guarantor Form."

**Frontend Action:** User clicks "Proceed to Guarantor Form" → Go to Step 17

**API Call:** `GET /api/bnpl/status/{application_id}` returns `status: 'approved'`

---

#### **Step 17: Guarantor Information**
**UI:** User provides guarantor details and uploads signed form

**Guarantor Details Form:**
- `guarantorName` (string, required) - Full name
- `guarantorPhone` (string, required) - Phone number
- `guarantorEmail` (string, optional) - Email address
- `guarantorRelationship` (string, optional) - Relationship (e.g., Spouse, Colleague)

**Frontend Action:**
1. User fills form → Calls `POST /api/bnpl/guarantor/invite`
2. After success → Shows "Download Guarantor Form" button
3. User downloads form (PDF)
4. User uploads signed form → Calls `POST /api/bnpl/guarantor/upload`
5. After upload success → Go to Step 21 (Invoice)

---

#### **Step 21: Order Summary & Invoice**
**UI:** Displays final invoice with payment schedule

**Invoice Breakdown:**
- Product Price: ₦X
- Material Cost: ₦50,000
- Installation Fee: ₦50,000
- Delivery/Logistics: ₦25,000
- Inspection Fee: ₦10,000
- Insurance (0.5%): ₦X (compulsory for BNPL)
- **Total**

**Payment Schedule:**
- Initial Deposit (30%): ₦X (from loan calculator)
- Monthly Repayment: ₦X (from loan calculator)

**Note:** "Installation fees may change after site inspection. Any difference will be updated and shared with you for a one-off payment before installation."

**Frontend Action:** User clicks "Proceed to Checkout" → Payment gateway

---

### BNPL API Endpoints

#### **1. Get Customer Types (Configuration)**
**Endpoint:** `GET /api/config/customer-types`

**Headers:**
```
Accept: application/json
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
      "label": "Commercial & Industrial"
    }
  ]
}
```

**Note:** If API fails, frontend uses hardcoded fallback values.

---

#### **2. Get Audit Types (Configuration)**
**Endpoint:** `GET /api/config/audit-types`

**Headers:**
```
Accept: application/json
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
  ]
}
```

**Note:** If API fails, frontend uses hardcoded fallback values.

---

#### **3. Submit Loan Application**
**Endpoint:** `POST /api/bnpl/apply`

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: multipart/form-data
Accept: application/json
```

**Request Body (FormData):**

**Basic Fields:**
- `customer_type` (string, required): `residential` | `sme` | `commercial`
- `product_category` (string, required): `full-kit` | `inverter-battery` | `battery-only` | `inverter-only` | `panels-only`
- `loan_amount` (number, required): Total loan amount (from loan calculator `totalRepayment`)
- `repayment_duration` (number, required): Tenor in months (3, 6, 9, or 12)
- `credit_check_method` (string, required): `auto` | `manual`

**Personal Details (Nested):**
- `personal_details[full_name]` (string, required)
- `personal_details[bvn]` (string, required, 11 digits)
- `personal_details[phone]` (string, required)
- `personal_details[email]` (string, required, valid email)
- `personal_details[social_media]` (string, optional)

**Property Details (Nested):**
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

**Example FormData:**
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
personal_details[social_media]: "@johndoe"
property_details[state]: "Lagos"
property_details[address]: "123 Main Street"
property_details[landmark]: "Near Market"
property_details[floors]: "2"
property_details[rooms]: "4"
property_details[is_gated_estate]: "0"
bank_statement: [File]
live_photo: [File]
```

**Response (Success):**
```json
{
  "status": "success",
  "message": "Application submitted successfully",
  "data": {
    "loan_application": {
      "id": 123,
      "user_id": 1,
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

**Error Response:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "personal_details.bvn": ["The BVN must be exactly 11 digits."],
    "loan_amount": ["Minimum loan amount is ₦1,500,000."]
  }
}
```

**Business Logic:**
1. **Validate minimum loan amount:** `loan_amount >= 1,500,000`
2. **Store files:** Upload `bank_statement` and `live_photo` to storage
3. **Create loan_application record** with all provided data
4. **Set status:** `pending`
5. **If `credit_check_method === 'auto'`:** Trigger automatic BVN verification (async)
6. **If `credit_check_method === 'manual'`:** Flag for manual review
7. **If `audit_type === 'commercial'`:** Do NOT generate invoice, notify admin instead

---

#### **4. Get Application Status**
**Endpoint:** `GET /api/bnpl/status/{application_id}`

**Headers:**
```
Authorization: Bearer {access_token}
Accept: application/json
```

**Response (Success):**
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
  }
}
```

**Status Values:**
- `pending` - Application under review
- `approved` - Application approved, proceed to guarantor
- `rejected` - Application rejected
- `counter_offer` - Counter offer available

**Business Logic:**
- Return current status of loan application
- If `status === 'approved'` → `guarantor_required: true`
- Provide `next_step` guidance for frontend

---

#### **5. Invite/Save Guarantor Details**
**Endpoint:** `POST /api/bnpl/guarantor/invite`

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

**Field Descriptions:**
- `loan_application_id` (integer, required): ID from Step 11 response
- `full_name` (string, required): Guarantor's full name
- `phone` (string, required): Guarantor's phone number
- `email` (string, optional): Guarantor's email
- `relationship` (string, optional): Relationship to applicant

**Response (Success):**
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

**Business Logic:**
1. Create `guarantors` record linked to `loan_application_id`
2. Set `status: 'pending'`
3. **Optional:** Send email/SMS to guarantor with form download link
4. Return guarantor ID for form upload

---

#### **6. Upload Signed Guarantor Form**
**Endpoint:** `POST /api/bnpl/guarantor/upload`

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: multipart/form-data
Accept: application/json
```

**Request Body (FormData):**
- `guarantor_id` (integer, required): ID from Step 5 response
- `signed_form` (file, required): PDF, JPG, or PNG (max size: 10MB)

**Response (Success):**
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

**Business Logic:**
1. Validate `guarantor_id` exists and belongs to user's application
2. Upload `signed_form` file to storage
3. Update `guarantors` record with `signed_form_path`
4. Set guarantor `status: 'pending'` (admin will review)
5. **Optional:** Notify admin that guarantor form is ready for review

---

#### **7. Get Calendar Slots (Audit Scheduling)**
**Endpoint:** `GET /api/calendar/slots`

**Query Parameters:**
- `type` (string, required): `audit` | `installation`
- `payment_date` (string, required): Date in `YYYY-MM-DD` format

**Business Logic:**
- For `type=audit` (BNPL):
  - Return slots starting **48 hours (2 days)** after `payment_date`
- For `type=installation` (Buy Now):
  - Return slots starting **72 hours (3 days)** after `payment_date`

**See Buy Now section for full endpoint details.**

---

### BNPL Data Flow Summary

```
Step 1: Customer Type
  ↓
Step 2: Product Category
  ↓
Step 3: Method Selection (if applicable)
  ↓
  ├─→ Step 4: Audit Type (if audit)
  │     ├─→ Step 5: Home/Office Details → Step 7: Audit Invoice
  │     └─→ Step 6: Commercial Notification
  │
  └─→ Step 8: Loan Calculator
        ↓
Step 10: Credit Check Method
  ↓
Step 11: Final Application Form
  ↓
POST /api/bnpl/apply
  ↓
Step 12: Application Submitted (Pending)
  ↓
GET /api/bnpl/status/{id} (polling)
  ↓
Step 13: Application Approved
  ↓
Step 17: Guarantor Information
  ├─→ POST /api/bnpl/guarantor/invite
  └─→ POST /api/bnpl/guarantor/upload
  ↓
Step 21: Order Summary & Invoice
  ↓
Payment Gateway
```

---

## 🔄 Shared Components & Logic

### Loan Calculator Component

**Purpose:** Calculate loan terms (deposit, interest, monthly repayment)

**Inputs:**
- `totalAmount` (number): Product price + fees
- `depositPercent` (number, 30-80%, default: 30%)
- `tenor` (number, 3/6/9/12 months, default: 12)
- `interestRate` (number, fixed: 4% monthly)

**Calculations:**
```javascript
depositAmount = (totalAmount * depositPercent) / 100
principal = totalAmount - depositAmount
totalInterest = principal * (interestRate / 100) * tenor
totalRepayment = principal + totalInterest
monthlyRepayment = totalRepayment / tenor
```

**Validation:**
- Minimum order value: **₦1,500,000**
- If `totalAmount < 1,500,000` → Show error message

**Output:**
```javascript
{
  depositPercent: 30,
  tenor: 12,
  depositAmount: 750000,
  monthlyRepayment: 183333.33,
  totalRepayment: 2750000
}
```

---

## 🗄️ Database Schema Requirements

### Update `loan_applications` Table

```sql
ALTER TABLE `loan_applications`
ADD COLUMN `customer_type` VARCHAR(50) NULL COMMENT 'residential, sme, commercial',
ADD COLUMN `product_category` VARCHAR(50) NULL,
ADD COLUMN `audit_type` VARCHAR(50) NULL COMMENT 'home-office, commercial',
ADD COLUMN `property_state` VARCHAR(100) NULL,
ADD COLUMN `property_address` TEXT NULL,
ADD COLUMN `property_landmark` VARCHAR(255) NULL,
ADD COLUMN `property_floors` INT NULL,
ADD COLUMN `property_rooms` INT NULL,
ADD COLUMN `is_gated_estate` BOOLEAN DEFAULT 0,
ADD COLUMN `estate_name` VARCHAR(255) NULL,
ADD COLUMN `estate_address` TEXT NULL,
ADD COLUMN `credit_check_method` VARCHAR(50) NULL COMMENT 'auto, manual',
ADD COLUMN `bank_statement_path` VARCHAR(255) NULL,
ADD COLUMN `live_photo_path` VARCHAR(255) NULL,
ADD COLUMN `social_media_handle` VARCHAR(255) NULL,
ADD COLUMN `guarantor_id` BIGINT UNSIGNED NULL;
```

### Create `guarantors` Table

```sql
CREATE TABLE `guarantors` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'The applicant',
  `loan_application_id` BIGINT UNSIGNED NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NULL,
  `phone` VARCHAR(255) NOT NULL,
  `bvn` VARCHAR(11) NULL,
  `relationship` VARCHAR(100) NULL,
  `status` VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, approved, rejected',
  `signed_form_path` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`loan_application_id`) REFERENCES `loan_applications`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
```

### Update `orders` Table

```sql
ALTER TABLE `orders`
ADD COLUMN `material_cost` DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN `delivery_fee` DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN `inspection_fee` DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN `insurance_fee` DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN `order_type` VARCHAR(50) DEFAULT 'buy_now' COMMENT 'buy_now, bnpl, audit_only';
```

### Update `categories` Table

```sql
ALTER TABLE `categories`
ADD COLUMN `has_method_selection` BOOLEAN DEFAULT 0 COMMENT 'If 1, show Choose/Build/Audit step';
```

---

## 📡 API Endpoints Reference

### Buy Now Endpoints

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| POST | `/api/orders/checkout` | Generate invoice | Yes |
| GET | `/api/calendar/slots?type=installation&payment_date={date}` | Get installation slots | Yes |

### BNPL Endpoints

| Method | Endpoint | Purpose | Auth Required |
|--------|----------|---------|---------------|
| GET | `/api/config/customer-types` | Get customer type options | No |
| GET | `/api/config/audit-types` | Get audit type options | No |
| POST | `/api/bnpl/apply` | Submit loan application | Yes |
| GET | `/api/bnpl/status/{id}` | Get application status | Yes |
| POST | `/api/bnpl/guarantor/invite` | Save guarantor details | Yes |
| POST | `/api/bnpl/guarantor/upload` | Upload signed guarantor form | Yes |
| GET | `/api/calendar/slots?type=audit&payment_date={date}` | Get audit slots | Yes |

---

## ⚠️ Business Rules & Validation

### Buy Now Rules

1. **Installation Fee:**
   - If `installer_choice === 'troosolar'` → Charge ₦50,000 (or configurable)
   - If `installer_choice === 'own'` → Charge ₦0

2. **Insurance:**
   - **Optional** for Buy Now
   - If `include_insurance === true` → Charge 0.5% of product price
   - If `include_insurance === false` → Charge ₦0

3. **Calendar Slots:**
   - Installation slots available **72 hours** after payment date

4. **Invoice Calculation:**
   ```
   total = product_price + installation_fee + delivery_fee + insurance_fee + material_cost + inspection_fee
   ```

### BNPL Rules

1. **Minimum Order Value:**
   - **₦1,500,000** minimum
   - Reject applications below this amount

2. **Insurance:**
   - **Compulsory** for BNPL
   - Always charge 0.5% of product price

3. **Commercial Audits:**
   - If `audit_type === 'commercial'` → **DO NOT** generate instant invoice
   - Trigger admin notification for manual follow-up

4. **Credit Check:**
   - If `credit_check_method === 'auto'` → Trigger automatic BVN verification
   - If `credit_check_method === 'manual'` → Flag for manual review

5. **Application Status Flow:**
   - `pending` → Under review (24-48 hours)
   - `approved` → Proceed to guarantor form
   - `rejected` → Application denied
   - `counter_offer` → Alternative terms available

6. **Guarantor Requirements:**
   - Required after approval
   - Must provide: Full name, Phone (required), Email (optional), Relationship (optional)
   - Must upload signed guarantor form (PDF, JPG, PNG)

7. **Calendar Slots:**
   - Audit slots available **48 hours** after payment date

8. **Loan Calculator:**
   - Deposit: 30-80% (default: 30%)
   - Tenor: 3, 6, 9, or 12 months (default: 12)
   - Interest Rate: 4% monthly (fixed)

### Validation Rules

**BVN:**
- Must be exactly 11 digits
- Format: Numeric only

**Email:**
- Must be valid email format
- Example: `user@example.com`

**Phone:**
- Nigerian phone format
- Example: `08012345678`

**Files:**
- `bank_statement`: PDF, JPG, PNG (max 10MB)
- `live_photo`: JPG, PNG (max 5MB)
- `signed_form`: PDF, JPG, PNG (max 10MB)

**Dates:**
- Format: `YYYY-MM-DD`
- Example: `2025-12-01`

---

## 🚀 Implementation Checklist

### Backend Tasks

- [ ] Create/Update database tables (loan_applications, guarantors, orders)
- [ ] Implement `GET /api/config/customer-types`
- [ ] Implement `GET /api/config/audit-types`
- [ ] Implement `POST /api/orders/checkout` (Buy Now)
- [ ] Implement `POST /api/bnpl/apply` (BNPL)
- [ ] Implement `GET /api/bnpl/status/{id}`
- [ ] Implement `POST /api/bnpl/guarantor/invite`
- [ ] Implement `POST /api/bnpl/guarantor/upload`
- [ ] Implement `GET /api/calendar/slots`
- [ ] Add file upload handling (bank statement, live photo, guarantor form)
- [ ] Add validation for minimum loan amount (₦1,500,000)
- [ ] Add business logic for commercial audits (admin notification)
- [ ] Add calendar slot calculation (48h for audit, 72h for installation)
- [ ] Add insurance calculation (0.5% for BNPL, optional for Buy Now)
- [ ] Add installation fee logic (based on installer_choice)

### Testing Checklist

- [ ] Test Buy Now flow end-to-end
- [ ] Test BNPL flow end-to-end
- [ ] Test minimum loan amount validation
- [ ] Test file uploads (all file types)
- [ ] Test calendar slot calculations
- [ ] Test insurance calculations
- [ ] Test commercial audit notification
- [ ] Test guarantor flow
- [ ] Test error handling and validation messages
- [ ] Test authentication/authorization

---

## 📞 Support & Notes

### Response Format Standard

All API responses should follow this format:

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

### File Storage

- Store uploaded files in: `storage/loan_applications/` or `storage/guarantors/`
- Return relative paths in responses (e.g., `loan_applications/bank_statement_123.pdf`)
- Frontend will construct full URLs: `{BASE_URL}/storage/{path}`

### Authentication

- All endpoints (except config endpoints) require `Authorization: Bearer {token}` header
- Validate token on every request
- Return `401 Unauthorized` if token is invalid/missing

---

**Last Updated:** 2025-11-26  
**Frontend Version:** Current codebase  
**Backend Base URL:** `http://127.0.0.1:8000/api` (development) / `https://troosolar.hmstech.org/api` (production)

