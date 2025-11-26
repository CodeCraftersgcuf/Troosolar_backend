# TrooSolar Backend Implementation Guide

This document outlines the necessary backend updates, database schema changes, and API endpoints required to support the new **Buy Now** and **BNPL (Buy Now, Pay Later)** flows.

## 1. Database Schema Updates

Based on the existing `u667461137_troosolar.sql`, the following changes are required to support the new data requirements.

### A. Update `loan_applications` Table
The current table is missing fields for the detailed application form and property details.

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

### B. Create `guarantors` Table
To manage guarantor details separately and securely.

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
  `updated_at` TIMESTAMP NULL DEFAULT NULL
);
```

### C. Update `orders` Table
To support the detailed invoice breakdown (Insurance, Inspection, etc.).

```sql
ALTER TABLE `orders`
ADD COLUMN `material_cost` DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN `delivery_fee` DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN `inspection_fee` DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN `insurance_fee` DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN `order_type` VARCHAR(50) DEFAULT 'buy_now' COMMENT 'buy_now, bnpl, audit_only';
```

### D. Update `categories` Table
To control the flow dynamically (e.g., showing "Build System" option only for certain categories).

```sql
ALTER TABLE `categories`
ADD COLUMN `has_method_selection` BOOLEAN DEFAULT 0 COMMENT 'If 1, show Choose/Build/Audit step';
```

---

## 2. API Endpoints & Logic

### A. Configuration & Options (Dynamic Flow)
These endpoints allow the frontend to render options dynamically.

*   **`GET /api/config/customer-types`**
    *   **Response:** `[{ "id": "residential", "label": "For Residential" }, ...]`
*   **`GET /api/config/audit-types`**
    *   **Response:** `[{ "id": "home-office", "label": "Home / Office" }, ...]`

### B. BNPL Flow Endpoints

#### 1. Submit Loan Application (Step 11)
*   **Endpoint:** `POST /api/bnpl/apply`
*   **Payload:**
    ```json
    {
      "customer_type": "residential",
      "product_category": "full-kit",
      "loan_amount": 2500000,
      "repayment_duration": 6,
      "personal_details": {
        "full_name": "John Doe",
        "bvn": "12345678901",
        "phone": "08012345678",
        "social_media": "@johndoe"
      },
      "property_details": {
        "state": "Lagos",
        "address": "123 Street",
        "is_gated_estate": true,
        "estate_name": "Sunshine Estate"
      },
      "files": {
        "bank_statement": "(Binary/Multipart)",
        "live_photo": "(Binary/Multipart)"
      }
    }
    ```

#### 2. Guarantor Management
*   **`POST /api/bnpl/guarantor/invite`**: Sends email/SMS to guarantor or stores details.
*   **`POST /api/bnpl/guarantor/upload`**: Uploads the signed guarantor form.

#### 3. Credit Check Status
*   **`GET /api/bnpl/status/{application_id}`**: Returns status (`pending`, `approved`, `rejected`, `counter_offer`).

### C. Buy Now Flow Endpoints

#### 1. Checkout & Invoice
*   **Endpoint:** `POST /api/orders/checkout`
*   **Payload:**
    ```json
    {
      "product_id": 123,
      "installer_choice": "troosolar", // or 'own'
      "include_insurance": true
    }
    ```
*   **Response (Invoice Preview):**
    ```json
    {
      "product_price": 2000000,
      "installation_fee": 50000,
      "insurance_fee": 10000,
      "delivery_fee": 25000,
      "total": 2085000
    }
    ```

### D. Scheduling (Calendar)
*   **Endpoint:** `GET /api/calendar/slots`
*   **Query Params:** `type=audit|installation`, `payment_date=YYYY-MM-DD`
*   **Logic:**
    *   If `type=audit` (BNPL), return slots starting **48 hours** after `payment_date`.
    *   If `type=installation` (Buy Now), return slots starting **72 hours** after `payment_date`.

---

## 3. Critical Logic Notes

1.  **BNPL Minimum Order:** Ensure the backend rejects BNPL applications where `loan_amount` < ₦1,500,000.
2.  **Commercial Audits:** If `audit_type` is 'commercial', do **not** generate an instant invoice. Instead, trigger a notification to the admin team for a manual follow-up.
3.  **Insurance:** For BNPL, insurance (0.5%) is **compulsory**. For Buy Now, it is **optional**.
