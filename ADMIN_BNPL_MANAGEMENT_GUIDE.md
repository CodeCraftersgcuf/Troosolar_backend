# Admin BNPL Management Guide

This comprehensive guide is for the admin team to manage Buy Now, Pay Later (BNPL) applications, guarantors, and orders effectively.

---

## Table of Contents

1. [Overview](#overview)
2. [BNPL Application Management](#bnpl-application-management)
3. [Guarantor Management](#guarantor-management)
4. [Order Management](#order-management)
5. [Status Workflow](#status-workflow)
6. [Key Actions & Decisions](#key-actions--decisions)
7. [Common Scenarios](#common-scenarios)
8. [API Endpoints Reference](#api-endpoints-reference)

---

## Overview

The BNPL system allows customers to purchase solar systems with financing options. As an admin, you'll manage:

- **BNPL Applications**: Review and approve/reject loan applications
- **Guarantors**: Verify guarantor information and documents
- **Orders**: Monitor BNPL orders and their status
- **Counter Offers**: Handle partial approvals with modified terms

---

## BNPL Application Management

### Viewing All BNPL Applications

**Endpoint:** `GET /api/admin/bnpl/applications`

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `approved`, `rejected`, `counter_offer`)
- `customer_type` (optional): Filter by customer type (`residential`, `sme`, `commercial`)
- `search` (optional): Search by user name or email
- `per_page` (optional): Results per page (default: 15)

**Example Request:**
```
GET /api/admin/bnpl/applications?status=pending&customer_type=residential&per_page=20
```

**Response:**
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
          "email": "john@example.com"
        },
        "customer_type": "residential",
        "product_category": "full-kit",
        "loan_amount": 2500000,
        "repayment_duration": 12,
        "status": "pending",
        "created_at": "2025-11-27T10:00:00.000000Z"
      }
    ],
    "total": 50,
    "per_page": 15
  },
  "message": "BNPL applications retrieved successfully"
}
```

### Viewing Single Application Details

**Endpoint:** `GET /api/admin/bnpl/applications/{id}`

**Response Includes:**
- User information
- Application details (customer type, product category, loan amount, repayment duration)
- Property details (state, address, floors, rooms, gated estate info)
- Credit check method (auto via Mono or manual upload)
- Bank statement and live photo paths
- Social media handle
- Guarantor information (if available)
- Loan calculation details

**Key Information to Review:**
1. **Loan Amount**: Must be ≥ ₦1,500,000
2. **Credit Check Results**: Review bank statement and Mono credit check results
3. **Property Details**: Verify address and property specifications
4. **Social Media**: Verify customer's social media presence
5. **Guarantor Status**: Check if guarantor has been invited and approved

### Updating Application Status

**Endpoint:** `PUT /api/admin/bnpl/applications/{id}/status`

**Request Body:**
```json
{
  "status": "approved",  // "approved", "rejected", or "counter_offer"
  "admin_notes": "Application approved. Customer meets all requirements.",
  "counter_offer": {  // Required only if status is "counter_offer"
    "minimum_deposit": 1000000,
    "minimum_tenor": 6
  }
}
```

**Status Options:**

1. **`approved`**: Application is fully approved
   - Customer can proceed with loan disbursement
   - Guarantor verification can begin

2. **`rejected`**: Application is rejected
   - Customer will be notified
   - They can reapply with different terms

3. **`counter_offer`**: Partial approval with modified terms
   - Requires `counter_offer` object with:
     - `minimum_deposit`: Minimum upfront payment required
     - `minimum_tenor`: Minimum repayment duration in months
   - Customer can accept or reject the counter offer

**Example Counter Offer:**
```json
{
  "status": "counter_offer",
  "admin_notes": "Customer qualifies but needs higher deposit. Minimum 40% deposit and 6-month tenor required.",
  "counter_offer": {
    "minimum_deposit": 1000000,
    "minimum_tenor": 6
  }
}
```

---

## Guarantor Management

### Viewing All Guarantors

**Endpoint:** `GET /api/admin/bnpl/guarantors`

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `approved`, `rejected`)
- `search` (optional): Search by name, email, or phone

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "loan_application_id": 5,
      "user": {
        "id": 2,
        "first_name": "John",
        "sur_name": "Doe"
      },
      "full_name": "Jane Smith",
      "email": "jane@example.com",
      "phone": "08012345678",
      "bvn": "12345678901",
      "relationship": "Spouse",
      "status": "pending",
      "signed_form_path": null,
      "created_at": "2025-11-27T10:00:00.000000Z"
    }
  ],
  "message": "Guarantors retrieved successfully"
}
```

### Updating Guarantor Status

**Endpoint:** `PUT /api/admin/bnpl/guarantors/{id}/status`

**Request Body:**
```json
{
  "status": "approved",  // "approved" or "rejected"
  "admin_notes": "Guarantor credit check passed. All documents verified."
}
```

**Important Notes:**
- If guarantor is **rejected**, the loan application cannot proceed
- If guarantor is **approved**, the loan can be disbursed (if application is also approved)
- Always verify:
  - BVN matches guarantor details
  - Signed guarantor form is uploaded
  - Credit check results are satisfactory

---

## Order Management

### Viewing BNPL Orders

**Endpoint:** `GET /api/admin/orders/bnpl`

**Query Parameters:**
- `status` (optional): Filter by order status
- `payment_status` (optional): Filter by payment status
- `search` (optional): Search by order number or user details

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 123,
      "order_number": "ORD123456",
      "user": {
        "id": 2,
        "first_name": "John",
        "sur_name": "Doe",
        "email": "john@example.com"
      },
      "total_price": 2500000,
      "payment_status": "paid",
      "order_status": "pending",
      "order_type": "bnpl",
      "created_at": "2025-11-27T10:00:00.000000Z"
    }
  ],
  "message": "BNPL orders retrieved successfully"
}
```

### Viewing Single BNPL Order

**Endpoint:** `GET /api/admin/orders/bnpl/{id}`

**Response Includes:**
- Order details (number, total, status)
- User information
- Product/bundle details
- Loan application details
- Payment information
- Delivery address

---

## Status Workflow

### Application Status Flow

```
pending → [Admin Review] → approved / rejected / counter_offer
                              ↓
                         [Customer Accepts Counter Offer]
                              ↓
                         approved
```

### Guarantor Status Flow

```
pending → [Admin Review] → approved / rejected
```

### Order Status Flow

```
pending → [Payment Confirmed] → processing → delivered
```

---

## Key Actions & Decisions

### When to Approve an Application

✅ **Approve if:**
- Loan amount ≥ ₦1,500,000
- Credit check results are satisfactory
- Bank statement shows consistent income
- Property details are verified
- Social media handle is valid
- All required documents are uploaded

### When to Reject an Application

❌ **Reject if:**
- Credit check shows poor credit history
- Bank statement shows insufficient income
- Loan amount < ₦1,500,000
- Documents are missing or invalid
- Property details cannot be verified

### When to Make a Counter Offer

💡 **Counter Offer if:**
- Customer has good credit but needs higher deposit
- Loan amount is acceptable but repayment duration needs adjustment
- Customer qualifies but needs modified terms

**Counter Offer Guidelines:**
- Minimum deposit: 30-50% of total amount
- Minimum tenor: 3-12 months (based on customer's financial capacity)
- Always provide clear explanation in `admin_notes`

### Guarantor Approval Criteria

✅ **Approve Guarantor if:**
- BVN verification passes
- Credit check is satisfactory
- Signed guarantor form is uploaded
- Relationship to applicant is verified
- Contact information is valid

❌ **Reject Guarantor if:**
- Credit check fails
- BVN doesn't match details
- Signed form is missing
- Contact information is invalid

---

## Common Scenarios

### Scenario 1: Application Needs Higher Deposit

**Situation:** Customer applies for ₦2,500,000 loan with 30% deposit, but credit check suggests they need 40% deposit.

**Action:**
1. Update application status to `counter_offer`
2. Set `minimum_deposit` to ₦1,000,000 (40% of ₦2,500,000)
3. Set `minimum_tenor` to 6 months (if needed)
4. Add admin notes explaining the requirement

**Request:**
```json
PUT /api/admin/bnpl/applications/5/status
{
  "status": "counter_offer",
  "admin_notes": "Based on credit check, customer needs to increase deposit to 40% (₦1,000,000) and minimum 6-month repayment period.",
  "counter_offer": {
    "minimum_deposit": 1000000,
    "minimum_tenor": 6
  }
}
```

### Scenario 2: Guarantor Credit Check Fails

**Situation:** Guarantor's credit check shows poor credit history.

**Action:**
1. Update guarantor status to `rejected`
2. Add admin notes explaining the rejection
3. Notify customer that they need a different guarantor

**Request:**
```json
PUT /api/admin/bnpl/guarantors/3/status
{
  "status": "rejected",
  "admin_notes": "Guarantor credit check failed. Credit history shows multiple defaults. Please provide a different guarantor with better credit standing."
}
```

### Scenario 3: Application Approved, Waiting for Guarantor

**Situation:** Application is approved, but guarantor verification is pending.

**Action:**
1. Application status: `approved`
2. Monitor guarantor status
3. Once guarantor is approved, loan can be disbursed
4. Update order status accordingly

### Scenario 4: Commercial Audit Request

**Situation:** Customer submits commercial audit request.

**Action:**
1. Review audit request via `GET /api/admin/audit/requests`
2. For commercial audits, manual follow-up is required
3. Approve audit request after verification
4. Customer can then proceed with payment and scheduling

---

## API Endpoints Reference

### BNPL Application Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/bnpl/applications` | List all BNPL applications |
| GET | `/api/admin/bnpl/applications/{id}` | Get single application details |
| PUT | `/api/admin/bnpl/applications/{id}/status` | Update application status |

### Guarantor Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/bnpl/guarantors` | List all guarantors |
| PUT | `/api/admin/bnpl/guarantors/{id}/status` | Update guarantor status |

### Order Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/orders/bnpl` | List all BNPL orders |
| GET | `/api/admin/orders/bnpl/{id}` | Get single BNPL order |

### Audit Request Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/audit/requests` | List all audit requests |
| GET | `/api/admin/audit/requests/{id}` | Get single audit request |
| PUT | `/api/admin/audit/requests/{id}/status` | Approve/reject audit request |

---

## Best Practices

1. **Review Applications Promptly**
   - Aim to review within 24-48 hours as per customer expectations
   - Set up notifications for new applications

2. **Document All Decisions**
   - Always add `admin_notes` when updating status
   - Explain reasons for approval, rejection, or counter offers

3. **Verify All Information**
   - Cross-check bank statements with credit check results
   - Verify property details match application
   - Confirm guarantor relationship and contact information

4. **Communicate Clearly**
   - Use clear, professional language in admin notes
   - Explain counter offer terms clearly
   - Provide actionable feedback for rejections

5. **Monitor Status Changes**
   - Track application → guarantor → order flow
   - Ensure all steps are completed before disbursement
   - Update order status as installation progresses

---

## Troubleshooting

### Application Not Showing Up

- Check filters (status, customer_type)
- Verify user has submitted application
- Check if application was deleted

### Cannot Update Status

- Verify application ID is correct
- Check if status transition is valid
- Ensure required fields are provided (especially for counter_offer)

### Guarantor Status Not Updating

- Verify guarantor ID matches application
- Check if signed form is uploaded
- Ensure status value is valid ("approved" or "rejected")

---

## Support Contacts

For technical issues or questions:
- Backend Team: [Contact Information]
- Admin Support: [Contact Information]

---

**Last Updated:** November 27, 2025

**Version:** 1.0

