# User BNPL Applications API Guide

**Last Updated:** 2024-12-27  
**Purpose:** Complete guide for users to view and manage their BNPL applications

---

## 📍 User BNPL Endpoints

### 1. Get All BNPL Applications (List)
**GET** `/api/bnpl/applications`

Get paginated list of all BNPL applications for the authenticated user.

#### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `status` | string | No | - | Filter by status: `pending`, `approved`, `rejected`, `counter_offer` |
| `per_page` | integer | No | 15 | Items per page |

#### Request Examples
```
GET /api/bnpl/applications
GET /api/bnpl/applications?status=pending
GET /api/bnpl/applications?status=approved
GET /api/bnpl/applications?per_page=20
```

#### Response (Success)
```json
{
  "status": "success",
  "message": "BNPL applications retrieved successfully",
  "data": {
    "data": [
      {
        "id": 22,
        "customer_type": "residential",
        "product_category": "full-kit",
        "loan_amount": "2,500,000.00",
        "repayment_duration": 12,
        "status": "pending",
        "property_state": "Lagos",
        "property_address": "123 Main Street, Ikeja",
        "is_gated_estate": true,
        "guarantor": null,
        "created_at": "2024-12-27 10:30:00",
        "updated_at": "2024-12-27 10:30:00"
      },
      {
        "id": 21,
        "customer_type": "commercial",
        "product_category": "inverter-battery",
        "loan_amount": "5,000,000.00",
        "repayment_duration": 6,
        "status": "approved",
        "property_state": "Abuja",
        "property_address": "456 Business District",
        "is_gated_estate": false,
        "guarantor": {
          "id": 5,
          "full_name": "Jane Smith",
          "status": "approved"
        },
        "created_at": "2024-12-25 14:15:00",
        "updated_at": "2024-12-27 09:20:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 2,
      "per_page": 15,
      "total": 25,
      "from": 1,
      "to": 15
    }
  }
}
```

---

### 2. Get Single BNPL Application Details
**GET** `/api/bnpl/status/{application_id}`

Get detailed information about a specific BNPL application.

#### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `application_id` | integer | Yes | BNPL application ID |

#### Response (Success)
```json
{
  "status": "success",
  "message": "Application status retrieved successfully",
  "data": {
    "id": 22,
    "customer_type": "residential",
    "product_category": "full-kit",
    "loan_amount": "2,500,000.00",
    "repayment_duration": 12,
    "status": "pending",
    "property_state": "Lagos",
    "property_address": "123 Main Street, Ikeja",
    "property_landmark": "Near Shoprite",
    "property_floors": 2,
    "property_rooms": 5,
    "is_gated_estate": true,
    "estate_name": "Sunshine Estate",
    "estate_address": "Sunshine Estate, Phase 2",
    "credit_check_method": "auto",
    "social_media_handle": "@johndoe",
    "bank_statement_path": "loan_applications/bank_statement_123456.pdf",
    "live_photo_path": "loan_applications/live_photo_123456.jpg",
    "loan_calculation": {
      "loan_amount": "2,500,000.00",
      "repayment_duration": 12,
      "down_payment": "750,000.00",
      "total_amount": "3,200,000.00",
      "interest_rate": 4.0
    },
    "guarantor": {
      "id": 5,
      "full_name": "Jane Smith",
      "email": "jane@example.com",
      "phone": "+2348012345678",
      "status": "pending",
      "has_signed_form": false
    },
    "created_at": "2024-12-27 10:30:00",
    "updated_at": "2024-12-27 10:30:00"
  }
}
```

---

## 📊 Application Status Values

| Status | Description |
|--------|-------------|
| `pending` | Application submitted, awaiting admin review |
| `approved` | Application approved, proceed to guarantor step |
| `rejected` | Application rejected |
| `counter_offer` | Admin made a counter offer with different terms |
| `counter_offer_accepted` | User accepted the counter offer |

---

## 💡 Usage Examples

### Frontend Integration

#### React/Vue Example - Get All Applications
```javascript
async function getUserBNPLApplications(status = null) {
  try {
    const params = new URLSearchParams();
    if (status) params.append('status', status);
    params.append('per_page', 15);
    
    const response = await fetch(`/api/bnpl/applications?${params}`, {
      headers: {
        'Authorization': `Bearer ${userToken}`,
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      const applications = data.data.data;
      
      applications.forEach(app => {
        console.log(`Application ID: ${app.id}`);
        console.log(`Status: ${app.status}`);
        console.log(`Loan Amount: ₦${app.loan_amount}`);
        console.log(`Duration: ${app.repayment_duration} months`);
        
        if (app.guarantor) {
          console.log(`Guarantor: ${app.guarantor.full_name} (${app.guarantor.status})`);
        }
      });
      
      return applications;
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// Usage
getUserBNPLApplications(); // Get all applications
getUserBNPLApplications('pending'); // Get only pending
getUserBNPLApplications('approved'); // Get only approved
```

#### React/Vue Example - Get Application Details
```javascript
async function getBNPLApplicationDetails(applicationId) {
  try {
    const response = await fetch(`/api/bnpl/status/${applicationId}`, {
      headers: {
        'Authorization': `Bearer ${userToken}`,
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      const app = data.data;
      
      console.log('Application Details:');
      console.log(`Status: ${app.status}`);
      console.log(`Loan Amount: ₦${app.loan_amount}`);
      console.log(`Property: ${app.property_address}, ${app.property_state}`);
      
      if (app.loan_calculation) {
        console.log(`Down Payment: ₦${app.loan_calculation.down_payment}`);
        console.log(`Total Amount: ₦${app.loan_calculation.total_amount}`);
      }
      
      if (app.guarantor) {
        console.log(`Guarantor: ${app.guarantor.full_name}`);
        console.log(`Guarantor Status: ${app.guarantor.status}`);
        console.log(`Has Signed Form: ${app.guarantor.has_signed_form}`);
      }
      
      return app;
    } else {
      console.error('Error:', data.message);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// Usage
getBNPLApplicationDetails(22);
```

---

## 🔍 Status-Based Workflow

### Pending Status
- Application submitted
- User should wait for admin review
- No action required from user

### Approved Status
- Admin approved the application
- User should proceed to guarantor step:
  - `POST /api/bnpl/guarantor/invite` - Invite/add guarantor
  - `POST /api/bnpl/guarantor/upload` - Upload signed guarantor form

### Counter Offer Status
- Admin proposed alternative terms
- User can accept or reject:
  - `POST /api/bnpl/counteroffer/accept` - Accept counter offer

### Rejected Status
- Application was rejected
- User can submit a new application

---

## 📋 Complete Response Fields

### List Response (getApplications)
- `id` - Application ID
- `customer_type` - residential, sme, commercial
- `product_category` - full-kit, inverter-battery, etc.
- `loan_amount` - Formatted loan amount
- `repayment_duration` - Tenor in months (3, 6, 9, 12)
- `status` - Current status
- `property_state` - Property state
- `property_address` - Property address
- `is_gated_estate` - Boolean
- `guarantor` - Guarantor info (if exists)
- `created_at` - Creation date
- `updated_at` - Last update date

### Details Response (getStatus)
All fields from list, plus:
- `property_landmark` - Landmark
- `property_floors` - Number of floors
- `property_rooms` - Number of rooms
- `estate_name` - Estate name (if gated)
- `estate_address` - Estate address (if gated)
- `credit_check_method` - auto or manual
- `social_media_handle` - Social media handle
- `bank_statement_path` - Bank statement file path
- `live_photo_path` - Live photo file path
- `loan_calculation` - Detailed loan calculation
- `guarantor` - Full guarantor details

---

## 🔗 Related Endpoints

### Submit Application
```
POST   /api/bnpl/apply  # Submit new BNPL application
```

### Guarantor Management
```
POST   /api/bnpl/guarantor/invite   # Invite/add guarantor
POST   /api/bnpl/guarantor/upload   # Upload signed form
```

### Counter Offer
```
POST   /api/bnpl/counteroffer/accept  # Accept counter offer
```

---

## ⚠️ Error Responses

### Application Not Found (404)
```json
{
  "status": "error",
  "message": "Application not found"
}
```

### Server Error (500)
```json
{
  "status": "error",
  "message": "Failed to retrieve BNPL applications: [error details]"
}
```

---

## ✅ Testing Checklist

- [ ] Get all applications (default)
- [ ] Filter by status (pending)
- [ ] Filter by status (approved)
- [ ] Filter by status (rejected)
- [ ] Filter by status (counter_offer)
- [ ] Get single application details
- [ ] Verify all fields are present
- [ ] Verify loan_calculation details
- [ ] Verify guarantor details
- [ ] Test pagination
- [ ] Test with invalid application ID

---

**End of Documentation**

