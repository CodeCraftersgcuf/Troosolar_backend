# Admin Audit Users API Guide

**Last Updated:** 2024-12-27  
**Purpose:** Guide for admin to view all users who have made audit requests

---

## 📍 Main Endpoint

### Get All Users With Audit Requests
**GET** `/api/admin/audit/users-with-requests`

Returns a paginated list of all users who have made audit requests, including user details, audit request statistics, and all audit requests for each user with property details.

---

## 🔐 Authentication

**Required:** Admin authentication via Sanctum
```
Headers:
  Authorization: Bearer {admin_token}
  Accept: application/json
```

---

## 📊 Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search by user name, email, or phone |
| `audit_type` | string | No | - | Filter by audit type: `home-office`, `commercial` |
| `has_pending` | boolean | No | - | Filter users who have pending requests |
| `sort_by` | string | No | `last_audit_request_date` | Sort field: `name`, `email`, `audit_request_count`, `last_audit_request_date`, `created_at` |
| `sort_order` | string | No | `desc` | Sort order: `asc` or `desc` |
| `per_page` | integer | No | 15 | Items per page |

---

## 📋 Request Examples

```
GET /api/admin/audit/users-with-requests
GET /api/admin/audit/users-with-requests?search=john
GET /api/admin/audit/users-with-requests?audit_type=commercial
GET /api/admin/audit/users-with-requests?has_pending=true
GET /api/admin/audit/users-with-requests?sort_by=audit_request_count&sort_order=desc
GET /api/admin/audit/users-with-requests?search=jane&audit_type=home-office&per_page=20
```

---

## 📤 Response Format

### Success Response
```json
{
  "status": "success",
  "message": "Users with audit requests retrieved successfully",
  "data": {
    "data": [
      {
        "id": 5,
        "name": "John Doe",
        "email": "john.doe@example.com",
        "phone": "+2348012345678",
        "audit_request_count": 2,
        "pending_count": 1,
        "approved_count": 1,
        "rejected_count": 0,
        "completed_count": 0,
        "last_audit_request_date": "2024-12-27 14:30:00",
        "user_created_at": "2024-12-20 10:00:00",
        "audit_requests": [
          {
            "id": 1,
            "audit_type": "home-office",
            "customer_type": "residential",
            "status": "pending",
            "property_state": "Lagos",
            "property_address": "123 Main Street, Ikeja",
            "property_floors": 2,
            "property_rooms": 5,
            "is_gated_estate": true,
            "has_property_details": true,
            "order_id": null,
            "created_at": "2024-12-27 14:30:00"
          },
          {
            "id": 2,
            "audit_type": "commercial",
            "customer_type": "commercial",
            "status": "approved",
            "property_state": null,
            "property_address": null,
            "property_floors": null,
            "property_rooms": null,
            "is_gated_estate": false,
            "has_property_details": false,
            "order_id": 123,
            "created_at": "2024-12-25 10:15:00"
          }
        ]
      },
      {
        "id": 7,
        "name": "Jane Smith",
        "email": "jane.smith@example.com",
        "phone": "+2348098765432",
        "audit_request_count": 1,
        "pending_count": 1,
        "approved_count": 0,
        "rejected_count": 0,
        "completed_count": 0,
        "last_audit_request_date": "2024-12-27 12:15:00",
        "user_created_at": "2024-12-25 08:30:00",
        "audit_requests": [
          {
            "id": 3,
            "audit_type": "home-office",
            "customer_type": "residential",
            "status": "pending",
            "property_state": "Abuja",
            "property_address": "456 Business District",
            "property_floors": 1,
            "property_rooms": 3,
            "is_gated_estate": false,
            "has_property_details": true,
            "order_id": null,
            "created_at": "2024-12-27 12:15:00"
          }
        ]
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 15,
      "total": 42,
      "from": 1,
      "to": 15
    }
  }
}
```

---

## 📊 Response Fields

### User Information
| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | User ID |
| `name` | string | Full name (first_name + sur_name) |
| `email` | string | User email |
| `phone` | string | User phone number |

### Audit Request Statistics
| Field | Type | Description |
|-------|------|-------------|
| `audit_request_count` | integer | Total number of audit requests |
| `pending_count` | integer | Number of pending requests |
| `approved_count` | integer | Number of approved requests |
| `rejected_count` | integer | Number of rejected requests |
| `completed_count` | integer | Number of completed requests |
| `last_audit_request_date` | string | Date of most recent audit request |

### Audit Request Details
Each user has an `audit_requests` array with all their audit requests:

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Audit request ID |
| `audit_type` | string | Type: `home-office` or `commercial` |
| `customer_type` | string | Customer type: `residential`, `sme`, `commercial` |
| `status` | string | Status: `pending`, `approved`, `rejected`, `completed` |
| `property_state` | string\|null | Property state (null for commercial without details) |
| `property_address` | string\|null | Property address (null for commercial without details) |
| `property_floors` | integer\|null | Number of floors |
| `property_rooms` | integer\|null | Number of rooms |
| `is_gated_estate` | boolean | Whether property is in gated estate |
| `has_property_details` | boolean | **Key field** - `true` if user provided property details, `false` if admin needs to add |
| `order_id` | integer\|null | Linked order ID (if paid) |
| `created_at` | string | Request creation date |

---

## 🔍 Sorting Options

### Sort By Fields
- `name` - Sort by user's full name
- `email` - Sort by email address
- `audit_request_count` - Sort by number of audit requests
- `last_audit_request_date` - Sort by most recent request (default)
- `created_at` - Sort by user registration date

### Sort Order
- `asc` - Ascending order
- `desc` - Descending order (default)

---

## 🔎 Filtering

### Filter by Audit Type
```
GET /api/admin/audit/users-with-requests?audit_type=home-office
GET /api/admin/audit/users-with-requests?audit_type=commercial
```

### Filter Users with Pending Requests
```
GET /api/admin/audit/users-with-requests?has_pending=true
```
Returns only users who have at least one pending audit request.

### Search
Searches across:
- User's first name
- User's surname
- User's email
- User's phone number

---

## 💡 Usage Examples

### Frontend Integration

#### React/Vue Example - Get Users With Audit Requests
```javascript
async function getUsersWithAuditRequests(filters = {}) {
  try {
    const params = new URLSearchParams();
    
    if (filters.search) params.append('search', filters.search);
    if (filters.audit_type) params.append('audit_type', filters.audit_type);
    if (filters.has_pending) params.append('has_pending', 'true');
    if (filters.sort_by) params.append('sort_by', filters.sort_by);
    if (filters.sort_order) params.append('sort_order', filters.sort_order);
    if (filters.per_page) params.append('per_page', filters.per_page);
    
    const response = await fetch(`/api/admin/audit/users-with-requests?${params}`, {
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      const { data: users, pagination } = data.data;
      
      // Display users
      users.forEach(user => {
        console.log(`User: ${user.name}`);
        console.log(`Email: ${user.email}`);
        console.log(`Total Requests: ${user.audit_request_count}`);
        console.log(`Pending: ${user.pending_count}, Approved: ${user.approved_count}`);
        
        // Display audit requests
        user.audit_requests.forEach(request => {
          console.log(`  - ${request.audit_type} (${request.status})`);
          
          // Check if commercial needs property details
          if (request.audit_type === 'commercial' && !request.has_property_details) {
            console.log('    ⚠️ Commercial request - Admin needs to add property details');
          } else if (request.has_property_details) {
            console.log(`    Property: ${request.property_address}, ${request.property_state}`);
          }
        });
      });
      
      return { users, pagination };
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// Usage examples
getUsersWithAuditRequests(); // Get all users with audit requests
getUsersWithAuditRequests({ 
  audit_type: 'commercial',
  has_pending: true 
}); // Get commercial requests that need admin input
getUsersWithAuditRequests({ 
  search: 'john',
  sort_by: 'audit_request_count',
  sort_order: 'desc'
}); // Search and sort
```

---

## 🎯 Key Features

### 1. User-Centric View
Unlike `/api/admin/audit/requests` which lists individual requests, this endpoint groups by user, making it easy to see all requests from a single user.

### 2. Property Details Indicator
The `has_property_details` field is crucial:
- **`true`** - User provided property details (home-office typically)
- **`false`** - Admin needs to add property details (commercial typically)

### 3. Status Summary
Each user shows counts for:
- Total requests
- Pending requests
- Approved requests
- Rejected requests
- Completed requests

### 4. Complete Request History
All audit requests for each user are included in the `audit_requests` array, showing full history.

---

## ⚠️ Important Notes

### Commercial vs Home/Office

**Commercial Requests:**
- Usually `has_property_details: false`
- `property_address`, `property_state`, etc. are `null`
- Admin needs to add property details before approval

**Home/Office Requests:**
- Usually `has_property_details: true`
- All property fields are populated by user
- Admin can review and approve directly

### Filtering Commercial Requests Needing Admin Input
To find commercial requests where admin needs to add property details:
```javascript
// Frontend filtering example
const commercialNeedingDetails = users
  .flatMap(user => user.audit_requests)
  .filter(req => 
    req.audit_type === 'commercial' && 
    !req.has_property_details &&
    req.status === 'pending'
  );
```

---

## 🔗 Related Endpoints

### Audit Request Management
```
GET    /api/admin/audit/requests           # List all audit requests (request-centric)
GET    /api/admin/audit/requests/{id}      # Get single audit request details
PUT    /api/admin/audit/requests/{id}/status  # Update request status
```

### User Audit Requests (User-facing)
```
GET    /api/audit/requests                 # Get user's own audit requests
POST   /api/audit/request                  # Submit new audit request
```

---

## ✅ Testing Checklist

- [ ] Get all users with audit requests
- [ ] Filter by audit_type (home-office)
- [ ] Filter by audit_type (commercial)
- [ ] Filter by has_pending
- [ ] Search by name
- [ ] Search by email
- [ ] Sort by name
- [ ] Sort by audit_request_count
- [ ] Sort by last_audit_request_date
- [ ] Verify has_property_details field
- [ ] Verify status counts are correct
- [ ] Test pagination
- [ ] Verify all audit requests are included per user

---

**End of Documentation**

