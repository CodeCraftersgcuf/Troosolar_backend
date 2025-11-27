# Audit Request API Documentation

This document outlines the API endpoints for managing audit requests (Home/Office and Commercial/Industrial audits) with property details and admin approval functionality.

---

## Table of Contents

1. [User Endpoints](#user-endpoints)
2. [Admin Endpoints](#admin-endpoints)
3. [Integration Flow](#integration-flow)
4. [Request/Response Examples](#requestresponse-examples)

---

## User Endpoints

### 1. Submit Audit Request

**Endpoint:** `POST /api/audit/request`

**Authentication:** Required (Bearer Token)

**Description:** Submit an audit request with property details. This should be called before checkout to create the audit request record.

**Request Body:**
```json
{
  "audit_type": "home-office",  // Required: "home-office" or "commercial"
  "customer_type": "residential",  // Optional: "residential", "sme", "commercial"
  "property_state": "Lagos",  // Required
  "property_address": "123 Main Street, Ikeja",  // Required
  "property_landmark": "Near Shoprite",  // Optional
  "property_floors": 2,  // Optional, integer
  "property_rooms": 5,  // Optional, integer
  "is_gated_estate": true,  // Optional, boolean
  "estate_name": "Sunshine Estate",  // Required if is_gated_estate is true
  "estate_address": "Sunshine Estate, Phase 2, Block 5"  // Required if is_gated_estate is true
}
```

**Validation Rules:**
- `audit_type`: Required, must be "home-office" or "commercial"
- `property_state`: Required, string
- `property_address`: Required, string
- `property_landmark`: Optional, string (max 255)
- `property_floors`: Optional, integer (min 0)
- `property_rooms`: Optional, integer (min 0)
- `is_gated_estate`: Optional, boolean
- `estate_name`: Required if `is_gated_estate` is true
- `estate_address`: Required if `is_gated_estate` is true

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "audit_type": "home-office",
    "status": "pending",
    "property_state": "Lagos",
    "property_address": "123 Main Street, Ikeja",
    "created_at": "2025-11-27T11:30:00.000000Z"
  },
  "message": "Audit request submitted successfully"
}
```

**Response (Validation Error - 422):**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "property_state": ["The property state field is required."],
    "estate_name": ["The estate name field is required when is gated estate is true."]
  }
}
```

---

### 2. Get Audit Request Status

**Endpoint:** `GET /api/audit/request/{id}`

**Authentication:** Required (Bearer Token)

**Description:** Get the status and details of a specific audit request.

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "audit_type": "home-office",
    "status": "pending",
    "property_state": "Lagos",
    "property_address": "123 Main Street, Ikeja",
    "property_landmark": "Near Shoprite",
    "property_floors": 2,
    "property_rooms": 5,
    "is_gated_estate": true,
    "estate_name": "Sunshine Estate",
    "estate_address": "Sunshine Estate, Phase 2, Block 5",
    "admin_notes": null,
    "approved_by": null,
    "approved_at": null,
    "order_id": null,
    "created_at": "2025-11-27T11:30:00.000000Z"
  },
  "message": "Audit request retrieved successfully"
}
```

**Response (Not Found - 404):**
```json
{
  "status": "error",
  "message": "Audit request not found"
}
```

---

### 3. Get All User's Audit Requests

**Endpoint:** `GET /api/audit/requests`

**Authentication:** Required (Bearer Token)

**Description:** Get all audit requests for the authenticated user.

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "audit_type": "home-office",
      "status": "pending",
      "property_state": "Lagos",
      "property_address": "123 Main Street, Ikeja",
      "order_id": null,
      "order_number": null,
      "created_at": "2025-11-27T11:30:00.000000Z"
    },
    {
      "id": 2,
      "audit_type": "commercial",
      "status": "approved",
      "property_state": "Abuja",
      "property_address": "456 Business District",
      "order_id": 123,
      "order_number": "ORD123456",
      "created_at": "2025-11-26T10:00:00.000000Z"
    }
  ],
  "message": "Audit requests retrieved successfully"
}
```

---

## Admin Endpoints

### 1. Get All Audit Requests (Admin)

**Endpoint:** `GET /api/admin/audit/requests`

**Authentication:** Required (Bearer Token, Admin Role)

**Description:** Get all audit requests with filtering and search capabilities.

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `approved`, `rejected`, `completed`)
- `audit_type` (optional): Filter by audit type (`home-office`, `commercial`)
- `search` (optional): Search by user name, email, or property address
- `per_page` (optional): Number of results per page (default: 15)

**Example Request:**
```
GET /api/admin/audit/requests?status=pending&audit_type=home-office&search=Lagos&per_page=20
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user": {
          "id": 2,
          "first_name": "John",
          "sur_name": "Doe",
          "email": "john@example.com",
          "phone": "08012345678"
        },
        "audit_type": "home-office",
        "status": "pending",
        "property_state": "Lagos",
        "property_address": "123 Main Street, Ikeja",
        "order": {
          "id": 123,
          "order_number": "ORD123456",
          "total_price": 50000.00,
          "payment_status": "paid"
        },
        "created_at": "2025-11-27T11:30:00.000000Z"
      }
    ],
    "total": 50,
    "per_page": 15,
    "last_page": 4
  },
  "message": "Audit requests retrieved successfully"
}
```

---

### 2. Get Single Audit Request (Admin)

**Endpoint:** `GET /api/admin/audit/requests/{id}`

**Authentication:** Required (Bearer Token, Admin Role)

**Description:** Get detailed information about a specific audit request.

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "user": {
      "id": 2,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "08012345678"
    },
    "audit_type": "home-office",
    "customer_type": "residential",
    "property_state": "Lagos",
    "property_address": "123 Main Street, Ikeja",
    "property_landmark": "Near Shoprite",
    "property_floors": 2,
    "property_rooms": 5,
    "is_gated_estate": true,
    "estate_name": "Sunshine Estate",
    "estate_address": "Sunshine Estate, Phase 2, Block 5",
    "status": "pending",
    "admin_notes": null,
    "approved_by": null,
    "approved_at": null,
    "order": {
      "id": 123,
      "order_number": "ORD123456",
      "total_price": 50000.00,
      "payment_status": "paid",
      "order_status": "pending"
    },
    "created_at": "2025-11-27T11:30:00.000000Z",
    "updated_at": "2025-11-27T11:30:00.000000Z"
  },
  "message": "Audit request retrieved successfully"
}
```

---

### 3. Approve/Reject Audit Request (Admin)

**Endpoint:** `PUT /api/admin/audit/requests/{id}/status`

**Authentication:** Required (Bearer Token, Admin Role)

**Description:** Approve, reject, or mark an audit request as completed.

**Request Body:**
```json
{
  "status": "approved",  // Required: "approved", "rejected", or "completed"
  "admin_notes": "Property verified. Ready for audit scheduling."  // Optional
}
```

**Validation Rules:**
- `status`: Required, must be "approved", "rejected", or "completed"
- `admin_notes`: Optional, string (max 1000 characters)

**Response (Success - 200):**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "status": "approved",
    "admin_notes": "Property verified. Ready for audit scheduling.",
    "approved_by": {
      "id": 1,
      "name": "Admin User"
    },
    "approved_at": "2025-11-27T12:00:00.000000Z"
  },
  "message": "Audit request status updated successfully"
}
```

**Response (Validation Error - 422):**
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

## Integration Flow

### Complete Audit Request Flow

1. **User selects audit type** (Home/Office or Commercial/Industrial)
2. **User fills property details form**
3. **Submit audit request** → `POST /api/audit/request`
   - Returns `audit_request_id`
4. **Proceed to checkout** → `POST /api/orders/checkout`
   - Include `product_category: "audit"` and `audit_request_id` in request
   - Returns `order_id` and invoice details
5. **Make payment** → Payment gateway integration
6. **Confirm payment** → `POST /api/order/payment-confirmation`
   - Include `type: "audit"` in request
   - Order and audit request are linked
7. **Calendar booking** → `GET /api/calendar/slots?type=audit&payment_date=YYYY-MM-DD`
   - Slots available 48 hours after payment confirmation

### Admin Approval Flow

1. **Admin views audit requests** → `GET /api/admin/audit/requests`
2. **Admin reviews details** → `GET /api/admin/audit/requests/{id}`
3. **Admin approves/rejects** → `PUT /api/admin/audit/requests/{id}/status`
   - For commercial audits, admin must approve before invoice generation
   - For home-office audits, admin can approve after payment

---

## Request/Response Examples

### Example 1: Submit Home/Office Audit Request

**Request:**
```http
POST /api/audit/request
Authorization: Bearer {token}
Content-Type: application/json

{
  "audit_type": "home-office",
  "customer_type": "residential",
  "property_state": "Lagos",
  "property_address": "123 Main Street, Ikeja",
  "property_landmark": "Near Shoprite",
  "property_floors": 2,
  "property_rooms": 5,
  "is_gated_estate": true,
  "estate_name": "Sunshine Estate",
  "estate_address": "Sunshine Estate, Phase 2, Block 5"
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "audit_type": "home-office",
    "status": "pending",
    "property_state": "Lagos",
    "property_address": "123 Main Street, Ikeja",
    "created_at": "2025-11-27T11:30:00.000000Z"
  },
  "message": "Audit request submitted successfully"
}
```

---

### Example 2: Submit Commercial Audit Request

**Request:**
```http
POST /api/audit/request
Authorization: Bearer {token}
Content-Type: application/json

{
  "audit_type": "commercial",
  "customer_type": "commercial",
  "property_state": "Abuja",
  "property_address": "456 Business District, Wuse 2",
  "property_floors": 5,
  "property_rooms": 20,
  "is_gated_estate": false
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 2,
    "audit_type": "commercial",
    "status": "pending",
    "property_state": "Abuja",
    "property_address": "456 Business District, Wuse 2",
    "created_at": "2025-11-27T11:35:00.000000Z"
  },
  "message": "Audit request submitted successfully"
}
```

**Note:** For commercial audits, the system should notify admin for manual follow-up. Admin must approve before invoice generation.

---

### Example 3: Checkout with Audit Request

**Request:**
```http
POST /api/orders/checkout
Authorization: Bearer {token}
Content-Type: application/json

{
  "product_category": "audit",
  "audit_type": "home-office",
  "audit_request_id": 1,
  "amount": 50000
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "order_id": 123,
    "audit_fee": 50000.00,
    "total": 50000.00,
    "order_type": "audit",
    "audit_type": "home-office",
    "audit_request_id": 1,
    "created_at": "2025-11-27T11:40:00.000000Z"
  },
  "message": "Audit order created successfully"
}
```

---

### Example 4: Admin Approves Audit Request

**Request:**
```http
PUT /api/admin/audit/requests/1/status
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "status": "approved",
  "admin_notes": "Property verified. Ready for audit scheduling."
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "status": "approved",
    "admin_notes": "Property verified. Ready for audit scheduling.",
    "approved_by": {
      "id": 1,
      "name": "Admin User"
    },
    "approved_at": "2025-11-27T12:00:00.000000Z"
  },
  "message": "Audit request status updated successfully"
}
```

---

## Status Values

- **`pending`**: Audit request submitted, awaiting admin review or payment
- **`approved`**: Admin has approved the audit request
- **`rejected`**: Admin has rejected the audit request
- **`completed`**: Audit has been completed

---

## Important Notes

1. **Commercial Audits**: For `audit_type: "commercial"`, the system should notify admin for manual follow-up. Admin must approve before invoice generation.

2. **Home/Office Audits**: For `audit_type: "home-office"`, invoice can be generated immediately, but admin can still review and approve/reject.

3. **Gated Estate**: If `is_gated_estate` is `true`, both `estate_name` and `estate_address` are required.

4. **Order Linking**: When checkout is called with `audit_request_id`, the order is automatically linked to the audit request. After payment confirmation, the audit request's `order_id` is updated.

5. **Calendar Booking**: After payment confirmation, users can book audit slots using `GET /api/calendar/slots?type=audit&payment_date=YYYY-MM-DD`. Slots are available 48 hours after payment confirmation.

---

## Error Handling

All endpoints return standard error responses:

**400 Bad Request:**
```json
{
  "status": "error",
  "message": "Invalid request"
}
```

**401 Unauthorized:**
```json
{
  "status": "error",
  "message": "Unauthenticated"
}
```

**403 Forbidden:**
```json
{
  "status": "error",
  "message": "Unauthorized access"
}
```

**404 Not Found:**
```json
{
  "status": "error",
  "message": "Audit request not found"
}
```

**422 Validation Error:**
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

**500 Internal Server Error:**
```json
{
  "status": "error",
  "message": "Failed to process request"
}
```

---

**Last Updated:** November 27, 2025

