# Admin Audit Requests API Guide

**Last Updated:** 2024-12-27  
**Purpose:** Complete guide for admin to view and manage all audit requests from users

---

## 📋 Overview

Admins can view all audit requests made by users, filter by type (home-office/commercial), and manage their status. 

**Key Points:**
- **Home/Office**: User provides all property details upfront
- **Commercial**: User submits request, admin needs to add property details later
- All requests start with status `pending`

---

## 🔐 Authentication

**Required:** Admin authentication via Sanctum
```
Headers:
  Authorization: Bearer {admin_token}
  Accept: application/json
```

---

## 📍 API Endpoints

### 1. Get All Audit Requests (List)
**GET** `/api/admin/audit/requests`

Get paginated list of all audit requests with filtering and search options.

#### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `status` | string | No | - | Filter by status: `pending`, `approved`, `rejected`, `completed` |
| `audit_type` | string | No | - | Filter by type: `home-office`, `commercial` |
| `search` | string | No | - | Search by user name, email, or property address |
| `per_page` | integer | No | 15 | Items per page |

#### Request Examples
```
GET /api/admin/audit/requests
GET /api/admin/audit/requests?status=pending
GET /api/admin/audit/requests?audit_type=commercial
GET /api/admin/audit/requests?status=pending&audit_type=home-office
GET /api/admin/audit/requests?search=john
GET /api/admin/audit/requests?per_page=20
```

#### Response (Success)
```json
{
  "status": "success",
  "message": "Audit requests retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 5,
        "order_id": null,
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
        "created_at": "2024-12-27T10:30:00.000000Z",
        "updated_at": "2024-12-27T10:30:00.000000Z",
        "user": {
          "id": 5,
          "first_name": "John",
          "sur_name": "Doe",
          "email": "john.doe@example.com",
          "phone": "+2348012345678"
        },
        "order": null,
        "approver": null
      },
      {
        "id": 2,
        "user_id": 7,
        "order_id": null,
        "audit_type": "commercial",
        "customer_type": "commercial",
        "property_state": null,
        "property_address": null,
        "property_landmark": null,
        "property_floors": null,
        "property_rooms": null,
        "is_gated_estate": false,
        "estate_name": null,
        "estate_address": null,
        "status": "pending",
        "admin_notes": null,
        "approved_by": null,
        "approved_at": null,
        "created_at": "2024-12-27T11:15:00.000000Z",
        "updated_at": "2024-12-27T11:15:00.000000Z",
        "user": {
          "id": 7,
          "first_name": "Jane",
          "sur_name": "Smith",
          "email": "jane.smith@example.com",
          "phone": "+2348098765432"
        },
        "order": null,
        "approver": null
      }
    ],
    "first_page_url": "http://localhost:8000/api/admin/audit/requests?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "http://localhost:8000/api/admin/audit/requests?page=3",
    "links": [
      {
        "url": null,
        "label": "&laquo; Previous",
        "active": false
      },
      {
        "url": "http://localhost:8000/api/admin/audit/requests?page=1",
        "label": "1",
        "active": true
      }
    ],
    "next_page_url": "http://localhost:8000/api/admin/audit/requests?page=2",
    "path": "http://localhost:8000/api/admin/audit/requests",
    "per_page": 15,
    "prev_page_url": null,
    "to": 15,
    "total": 42
  }
}
```

---

### 2. Get Single Audit Request Details
**GET** `/api/admin/audit/requests/{id}`

Get detailed information about a specific audit request.

#### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Audit request ID |

#### Response (Success - Home/Office)
```json
{
  "status": "success",
  "message": "Audit request retrieved successfully",
  "data": {
    "id": 1,
    "user": {
      "id": 5,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "phone": "+2348012345678"
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
    "order": null,
    "created_at": "2024-12-27T10:30:00.000000Z",
    "updated_at": "2024-12-27T10:30:00.000000Z"
  }
}
```

#### Response (Success - Commercial)
```json
{
  "status": "success",
  "message": "Audit request retrieved successfully",
  "data": {
    "id": 2,
    "user": {
      "id": 7,
      "name": "Jane Smith",
      "email": "jane.smith@example.com",
      "phone": "+2348098765432"
    },
    "audit_type": "commercial",
    "customer_type": "commercial",
    "property_state": null,
    "property_address": null,
    "property_landmark": null,
    "property_floors": null,
    "property_rooms": null,
    "is_gated_estate": false,
    "estate_name": null,
    "estate_address": null,
    "status": "pending",
    "admin_notes": null,
    "approved_by": null,
    "approved_at": null,
    "order": null,
    "created_at": "2024-12-27T11:15:00.000000Z",
    "updated_at": "2024-12-27T11:15:00.000000Z"
  }
}
```

#### Response (Success - With Order)
```json
{
  "status": "success",
  "message": "Audit request retrieved successfully",
  "data": {
    "id": 1,
    "user": {
      "id": 5,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "phone": "+2348012345678"
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
    "status": "approved",
    "admin_notes": "Property verified, ready for audit scheduling",
    "approved_by": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@troosolar.com"
    },
    "approved_at": "2024-12-27T14:20:00.000000Z",
    "order": {
      "id": 123,
      "order_number": "ORD-2024-00123",
      "total_price": "50000.00",
      "payment_status": "confirmed",
      "order_status": "processing"
    },
    "created_at": "2024-12-27T10:30:00.000000Z",
    "updated_at": "2024-12-27T14:20:00.000000Z"
  }
}
```

---

### 3. Update Audit Request Status
**PUT** `/api/admin/audit/requests/{id}/status`

Approve, reject, or mark as completed. For commercial requests, admin should first add property details.

#### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | Audit request ID |

#### Request Body
```json
{
  "status": "approved",
  "admin_notes": "Property verified, ready for audit scheduling"
}
```

#### Field Descriptions
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `status` | string | Yes | Status: `approved`, `rejected`, `completed` |
| `admin_notes` | string | No | Admin notes/comments (max 1000 chars) |

#### Validation Rules
- `status`: Required, must be one of: `approved`, `rejected`, `completed`
- `admin_notes`: Optional, string, max 1000 characters

#### Response (Success)
```json
{
  "status": "success",
  "message": "Audit request status updated successfully",
  "data": {
    "id": 1,
    "status": "approved",
    "admin_notes": "Property verified, ready for audit scheduling",
    "approved_by": {
      "id": 1,
      "name": "Admin User"
    },
    "approved_at": "2024-12-27T14:20:00.000000Z"
  }
}
```

---

## 🔍 Filtering & Search

### Filter by Status
```
GET /api/admin/audit/requests?status=pending
GET /api/admin/audit/requests?status=approved
GET /api/admin/audit/requests?status=rejected
GET /api/admin/audit/requests?status=completed
```

### Filter by Audit Type
```
GET /api/admin/audit/requests?audit_type=home-office
GET /api/admin/audit/requests?audit_type=commercial
```

### Search
```
GET /api/admin/audit/requests?search=john
GET /api/admin/audit/requests?search=jane@example.com
GET /api/admin/audit/requests?search=Lagos
```

Searches across:
- User's first name
- User's surname
- User's email
- Property address
- Property state

### Combined Filters
```
GET /api/admin/audit/requests?status=pending&audit_type=commercial
GET /api/admin/audit/requests?status=pending&search=john&per_page=20
```

---

## 📊 Understanding Response Data

### Home/Office Audit Request
- ✅ **All property details provided by user**
- ✅ Ready for admin review and approval
- ✅ Can be approved immediately if data looks correct

**Example:**
```json
{
  "audit_type": "home-office",
  "property_state": "Lagos",
  "property_address": "123 Main Street, Ikeja",
  "property_floors": 2,
  "property_rooms": 5,
  "is_gated_estate": true,
  "estate_name": "Sunshine Estate"
}
```

### Commercial Audit Request
- ❌ **Property details missing** (admin needs to add)
- ❌ All property fields are `null`
- ⚠️ Admin should add property details before approval

**Example:**
```json
{
  "audit_type": "commercial",
  "property_state": null,
  "property_address": null,
  "property_floors": null,
  "property_rooms": null,
  "is_gated_estate": false,
  "estate_name": null
}
```

---

## 💡 Usage Examples

### Frontend Integration

#### React/Vue Example - Get All Requests
```javascript
async function getAuditRequests(filters = {}) {
  try {
    const params = new URLSearchParams();
    
    if (filters.status) params.append('status', filters.status);
    if (filters.audit_type) params.append('audit_type', filters.audit_type);
    if (filters.search) params.append('search', filters.search);
    if (filters.per_page) params.append('per_page', filters.per_page);
    
    const response = await fetch(`/api/admin/audit/requests?${params}`, {
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      const requests = data.data.data;
      
      // Display requests
      requests.forEach(request => {
        console.log(`ID: ${request.id}`);
        console.log(`Type: ${request.audit_type}`);
        console.log(`User: ${request.user.first_name} ${request.user.sur_name}`);
        console.log(`Status: ${request.status}`);
        
        // Check if commercial (needs admin input)
        if (request.audit_type === 'commercial' && !request.property_address) {
          console.log('⚠️ Commercial request - Admin needs to add property details');
        }
      });
      
      return data.data;
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// Usage
getAuditRequests({ status: 'pending', audit_type: 'commercial' });
```

#### React/Vue Example - Get Single Request
```javascript
async function getAuditRequestDetails(requestId) {
  try {
    const response = await fetch(`/api/admin/audit/requests/${requestId}`, {
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      const request = data.data;
      
      // Display details
      console.log('User:', request.user.name);
      console.log('Type:', request.audit_type);
      console.log('Status:', request.status);
      
      // Check if commercial needs property details
      if (request.audit_type === 'commercial' && !request.property_address) {
        console.log('⚠️ Commercial request - Add property details before approval');
      }
      
      return request;
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

#### React/Vue Example - Update Status
```javascript
async function updateAuditStatus(requestId, status, adminNotes = '') {
  try {
    const response = await fetch(`/api/admin/audit/requests/${requestId}/status`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        status: status, // 'approved', 'rejected', 'completed'
        admin_notes: adminNotes
      })
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      alert('Status updated successfully!');
      return data.data;
    } else {
      alert('Error: ' + data.message);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// Usage
updateAuditStatus(1, 'approved', 'Property verified, ready for scheduling');
updateAuditStatus(2, 'rejected', 'Incomplete property information');
```

---

## ⚠️ Important Notes

### Commercial Requests Workflow
1. User submits commercial audit request (no property details)
2. Admin views request - sees `property_address: null`, `property_state: null`, etc.
3. **Admin should add property details** (requires a separate update endpoint or manual database update)
4. Admin approves/rejects the request

### Home/Office Requests Workflow
1. User submits request with all property details
2. Admin reviews the provided details
3. Admin can approve/reject immediately

### Status Flow
```
pending → approved/rejected
approved → completed
```

---

## 🔗 Related Endpoints

### User Audit Request (Frontend)
```
POST   /api/audit/request           # User submits audit request
GET    /api/audit/request/{id}      # User checks status
GET    /api/audit/requests          # User's own requests
```

### Order Creation (After Approval)
```
POST   /api/orders/checkout         # Create audit order (for home-office after payment)
POST   /api/order/payment-confirmation  # Confirm payment (type: audit)
```

---

## ✅ Testing Checklist

- [ ] Get all audit requests
- [ ] Filter by status (pending, approved, rejected, completed)
- [ ] Filter by audit type (home-office, commercial)
- [ ] Search by user name
- [ ] Search by email
- [ ] Search by property address
- [ ] Get single request (home-office)
- [ ] Get single request (commercial)
- [ ] Update status to approved
- [ ] Update status to rejected
- [ ] Update status to completed
- [ ] Add admin notes
- [ ] Test pagination
- [ ] Verify commercial requests have null property fields

---

**End of Documentation**

