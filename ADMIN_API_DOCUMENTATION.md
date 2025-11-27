# TrooSolar Admin API Documentation

**Date:** November 26, 2025  
**Purpose:** Admin endpoints for managing BNPL and Buy Now flows

---

## 📋 Table of Contents

1. [BNPL Admin Endpoints](#bnpl-admin-endpoints)
2. [Buy Now Admin Endpoints](#buy-now-admin-endpoints)
3. [Authentication](#authentication)
4. [Response Format](#response-format)
5. [Error Handling](#error-handling)

---

## 🔐 Authentication

All admin endpoints require:
- **Authentication:** `Bearer {token}` (Sanctum)
- **Role:** Admin user (role check may be implemented in controllers)

**Headers:**
```
Authorization: Bearer {access_token}
Content-Type: application/json
Accept: application/json
```

---

## 📊 BNPL Admin Endpoints

### 1. Get All BNPL Applications

**Endpoint:** `GET /api/admin/bnpl/applications`

**Description:** Retrieve all BNPL loan applications with filtering and pagination.

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `approved`, `rejected`, `counter_offer`)
- `customer_type` (optional): Filter by customer type (`residential`, `sme`, `commercial`)
- `search` (optional): Search by user name or email
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number

**Example Request:**
```http
GET /api/admin/bnpl/applications?status=pending&per_page=20
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "user_id": 45,
        "customer_type": "residential",
        "product_category": "full-kit",
        "loan_amount": 2750000.00,
        "repayment_duration": 12,
        "status": "pending",
        "credit_check_method": "auto",
        "property_state": "Lagos",
        "property_address": "123 Main Street",
        "bank_statement_path": "loan_applications/bank_statement_123.pdf",
        "live_photo_path": "loan_applications/live_photo_123.jpg",
        "social_media_handle": "@johndoe",
        "created_at": "2025-11-26T10:30:00.000000Z",
        "user": {
          "id": 45,
          "first_name": "John",
          "sur_name": "Doe",
          "email": "john@example.com"
        },
        "guarantor": null,
        "mono": {
          "id": 12,
          "loan_amount": 2750000.00,
          "status": "pending"
        }
      }
    ],
    "per_page": 20,
    "total": 150
  },
  "message": "BNPL applications retrieved successfully"
}
```

---

### 2. Get Single BNPL Application

**Endpoint:** `GET /api/admin/bnpl/applications/{id}`

**Description:** Get detailed information about a specific BNPL application.

**Example Request:**
```http
GET /api/admin/bnpl/applications/123
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "id": 123,
    "user_id": 45,
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
    "user": {
      "id": 45,
      "first_name": "John",
      "sur_name": "Doe",
      "email": "john@example.com",
      "phone": "08012345678"
    },
    "guarantor": {
      "id": 456,
      "full_name": "Jane Doe",
      "phone": "08098765432",
      "email": "jane@example.com",
      "status": "pending"
    },
    "mono": {
      "id": 12,
      "loan_amount": 2750000.00,
      "repayment_duration": 12,
      "status": "pending",
      "loanCalculation": {
        "id": 10,
        "loan_amount": 2750000.00
      }
    }
  },
  "message": "BNPL application retrieved successfully"
}
```

**Error Response (404):**
```json
{
  "status": "error",
  "message": "BNPL application not found"
}
```

---

### 3. Update BNPL Application Status

**Endpoint:** `PUT /api/admin/bnpl/applications/{id}/status`

**Description:** Update the status of a BNPL application (approve, reject, or counter offer).

**Request Body:**
```json
{
  "status": "approved",
  "admin_notes": "Application approved after credit check review"
}
```

**Status Values:**
- `pending` - Under review
- `approved` - Application approved
- `rejected` - Application rejected
- `counter_offer` - Counter offer with modified terms

**For Counter Offer:**
```json
{
  "status": "counter_offer",
  "counter_offer_min_deposit": 900000,
  "counter_offer_min_tenor": 6,
  "admin_notes": "Requires higher deposit and shorter tenor"
}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "id": 123,
    "status": "approved",
    "admin_notes": "Application approved after credit check review",
    "updated_at": "2025-11-26T15:30:00.000000Z"
  },
  "message": "BNPL application status updated successfully"
}
```

**Error Response (422 - Validation):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "status": ["The status field is required."]
  }
}
```

---

### 4. Get All Guarantors

**Endpoint:** `GET /api/admin/bnpl/guarantors`

**Description:** Retrieve all guarantors with filtering options.

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `approved`, `rejected`)
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number

**Example Request:**
```http
GET /api/admin/bnpl/guarantors?status=pending
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 456,
        "user_id": 45,
        "loan_application_id": 123,
        "full_name": "Jane Doe",
        "phone": "08098765432",
        "email": "jane@example.com",
        "bvn": "12345678901",
        "relationship": "Spouse",
        "status": "pending",
        "signed_form_path": "guarantors/signed_form_456.pdf",
        "created_at": "2025-11-26T15:00:00.000000Z",
        "user": {
          "id": 45,
          "first_name": "John",
          "sur_name": "Doe"
        },
        "loanApplication": {
          "id": 123,
          "loan_amount": 2750000.00
        }
      }
    ],
    "per_page": 15,
    "total": 50
  },
  "message": "Guarantors retrieved successfully"
}
```

---

### 5. Update Guarantor Status

**Endpoint:** `PUT /api/admin/bnpl/guarantors/{id}/status`

**Description:** Approve or reject a guarantor after review.

**Request Body:**
```json
{
  "status": "approved",
  "admin_notes": "Guarantor credit check passed"
}
```

**Status Values:**
- `pending` - Under review
- `approved` - Guarantor approved
- `rejected` - Guarantor rejected

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "id": 456,
    "status": "approved",
    "admin_notes": "Guarantor credit check passed",
    "updated_at": "2025-11-26T16:00:00.000000Z"
  },
  "message": "Guarantor status updated successfully"
}
```

---

## 🛒 Buy Now Admin Endpoints

### 1. Get All Buy Now Orders

**Endpoint:** `GET /api/admin/orders/buy-now`

**Description:** Retrieve all Buy Now orders with filtering and pagination.

**Query Parameters:**
- `status` (optional): Filter by order status (`pending`, `processing`, `shipped`, `delivered`, `cancelled`)
- `search` (optional): Search by user name or email
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number

**Example Request:**
```http
GET /api/admin/orders/buy-now?status=pending&per_page=20
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 789,
        "user_id": 45,
        "order_type": "buy_now",
        "order_status": "pending",
        "total_price": 2647500.00,
        "product_price": 2500000.00,
        "installation_fee": 50000.00,
        "delivery_fee": 25000.00,
        "insurance_fee": 12500.00,
        "material_cost": 50000.00,
        "inspection_fee": 10000.00,
        "created_at": "2025-11-26T12:00:00.000000Z",
        "user": {
          "id": 45,
          "first_name": "John",
          "sur_name": "Doe",
          "email": "john@example.com",
          "phone": "08012345678"
        },
        "items": [
          {
            "id": 1,
            "itemable_type": "App\\Models\\Product",
            "itemable_id": 123,
            "quantity": 1,
            "price": 2500000.00,
            "itemable": {
              "id": 123,
              "name": "5KVA Solar System",
              "price": 2500000.00
            }
          }
        ],
        "deliveryAddress": {
          "id": 10,
          "address": "123 Main Street",
          "state": "Lagos"
        }
      }
    ],
    "per_page": 20,
    "total": 75
  },
  "message": "Buy Now orders retrieved successfully"
}
```

---

### 2. Get Single Buy Now Order

**Endpoint:** `GET /api/admin/orders/buy-now/{id}`

**Description:** Get detailed information about a specific Buy Now order.

**Example Request:**
```http
GET /api/admin/orders/buy-now/789
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "id": 789,
    "user_id": 45,
    "order_type": "buy_now",
    "order_status": "pending",
    "total_price": 2647500.00,
    "product_price": 2500000.00,
    "installation_fee": 50000.00,
    "delivery_fee": 25000.00,
    "insurance_fee": 12500.00,
    "material_cost": 50000.00,
    "inspection_fee": 10000.00,
    "installer_choice": "troosolar",
    "created_at": "2025-11-26T12:00:00.000000Z",
    "user": {
      "id": 45,
      "first_name": "John",
      "sur_name": "Doe",
      "email": "john@example.com",
      "phone": "08012345678"
    },
    "items": [
      {
        "id": 1,
        "itemable_type": "App\\Models\\Product",
        "itemable_id": 123,
        "quantity": 1,
        "price": 2500000.00,
        "itemable": {
          "id": 123,
          "name": "5KVA Solar System",
          "price": 2500000.00
        }
      }
    ],
    "deliveryAddress": {
      "id": 10,
      "address": "123 Main Street",
      "state": "Lagos",
      "city": "Ikeja"
    }
  },
  "message": "Buy Now order retrieved successfully"
}
```

---

### 3. Update Buy Now Order Status

**Endpoint:** `PUT /api/admin/orders/buy-now/{id}/status`

**Description:** Update the status of a Buy Now order.

**Request Body:**
```json
{
  "order_status": "processing",
  "admin_notes": "Order confirmed, preparing for shipment"
}
```

**Status Values:**
- `pending` - Order received, awaiting processing
- `processing` - Order being prepared
- `shipped` - Order shipped to customer
- `delivered` - Order delivered successfully
- `cancelled` - Order cancelled

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "id": 789,
    "order_status": "processing",
    "admin_notes": "Order confirmed, preparing for shipment",
    "updated_at": "2025-11-26T16:00:00.000000Z"
  },
  "message": "Buy Now order status updated successfully"
}
```

**Error Response (422 - Validation):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "order_status": ["The selected order status is invalid."]
  }
}
```

---

### 4. Get All BNPL Orders

**Endpoint:** `GET /api/admin/orders/bnpl`

**Description:** Retrieve all BNPL orders (orders created after BNPL application approval).

**Query Parameters:**
- `status` (optional): Filter by order status
- `search` (optional): Search by user name or email
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number

**Example Request:**
```http
GET /api/admin/orders/bnpl?status=pending
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 790,
        "user_id": 45,
        "order_type": "bnpl",
        "order_status": "pending",
        "total_price": 2750000.00,
        "loan_application_id": 123,
        "created_at": "2025-11-26T14:00:00.000000Z",
        "user": {
          "id": 45,
          "first_name": "John",
          "sur_name": "Doe",
          "email": "john@example.com"
        },
        "loanApplication": {
          "id": 123,
          "loan_amount": 2750000.00,
          "status": "approved"
        }
      }
    ],
    "per_page": 15,
    "total": 30
  },
  "message": "BNPL orders retrieved successfully"
}
```

---

### 5. Get Single BNPL Order

**Endpoint:** `GET /api/admin/orders/bnpl/{id}`

**Description:** Get detailed information about a specific BNPL order.

**Example Request:**
```http
GET /api/admin/orders/bnpl/790
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "id": 790,
    "user_id": 45,
    "order_type": "bnpl",
    "order_status": "pending",
    "total_price": 2750000.00,
    "loan_application_id": 123,
    "created_at": "2025-11-26T14:00:00.000000Z",
    "user": {
      "id": 45,
      "first_name": "John",
      "sur_name": "Doe",
      "email": "john@example.com"
    },
    "loanApplication": {
      "id": 123,
      "loan_amount": 2750000.00,
      "repayment_duration": 12,
      "status": "approved"
    },
    "items": [
      {
        "id": 2,
        "itemable_type": "App\\Models\\Product",
        "itemable_id": 123,
        "quantity": 1,
        "price": 2500000.00
      }
    ]
  },
  "message": "BNPL order retrieved successfully"
}
```

---

## 📝 Response Format

All responses follow this standard format:

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

---

## ⚠️ Error Handling

### Status Codes

- `200 OK` - Request successful
- `401 Unauthorized` - Missing or invalid authentication token
- `403 Forbidden` - User doesn't have admin privileges
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

**404 Not Found:**
```json
{
  "status": "error",
  "message": "BNPL application not found"
}
```

**422 Validation Error:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "status": ["The status field is required."],
    "counter_offer_min_deposit": ["The counter offer min deposit field is required when status is counter_offer."]
  }
}
```

---

## 🔗 Route Summary

### BNPL Admin Routes
```
GET    /api/admin/bnpl/applications              - List all BNPL applications
GET    /api/admin/bnpl/applications/{id}        - Get single BNPL application
PUT    /api/admin/bnpl/applications/{id}/status - Update application status
GET    /api/admin/bnpl/guarantors               - List all guarantors
PUT    /api/admin/bnpl/guarantors/{id}/status   - Update guarantor status
```

### Buy Now Admin Routes
```
GET    /api/admin/orders/buy-now                - List all Buy Now orders
GET    /api/admin/orders/buy-now/{id}           - Get single Buy Now order
PUT    /api/admin/orders/buy-now/{id}/status    - Update Buy Now order status
GET    /api/admin/orders/bnpl                   - List all BNPL orders
GET    /api/admin/orders/bnpl/{id}              - Get single BNPL order
```

---

## 📌 Important Notes

1. **Authentication Required:** All endpoints require Bearer token authentication
2. **Admin Role:** Controllers may check for admin role (implementation may vary)
3. **Pagination:** List endpoints support pagination with `per_page` and `page` parameters
4. **Filtering:** Most list endpoints support filtering by status and search
5. **Relationships:** Responses include related data (user, guarantor, items, etc.)
6. **File Paths:** File paths in responses are relative (e.g., `loan_applications/bank_statement_123.pdf`)

---

## 🚀 Implementation Status

✅ **Completed:**
- BNPL Admin Controller created
- Admin routes added
- Buy Now admin methods added to OrderController

⚠️ **Pending:**
- Admin role verification middleware (if needed)
- Email/SMS notifications on status updates
- Export functionality for reports

---

**Last Updated:** November 26, 2025

