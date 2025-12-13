# Admin Referral Management API Documentation

**Last Updated:** 2024-12-27  
**Purpose:** Complete API documentation for Admin Referral Management System

---

## 📋 Overview

The Referral Management API allows admins to:
- Configure referral commission percentage and minimum withdrawal amount
- View referral statistics and user referral data
- Manage referral settings

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

### 1. Get Referral Settings
**GET** `/api/admin/referral/settings`

Get current referral settings (commission percentage and minimum withdrawal).

#### Response (Success)
```json
{
  "status": "success",
  "message": "Referral settings retrieved successfully",
  "data": {
    "commission_percentage": 5.00,
    "minimum_withdrawal": 1000.00
  }
}
```

---

### 2. Update Referral Settings
**PUT** `/api/admin/referral/settings`

Update referral settings (commission percentage and/or minimum withdrawal).

#### Request Body
```json
{
  "commission_percentage": 5.00,
  "minimum_withdrawal": 1000.00
}
```

#### Field Descriptions
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `commission_percentage` | number | No | Commission percentage (0-100) |
| `minimum_withdrawal` | number | No | Minimum withdrawal amount (≥ 0) |

#### Validation Rules
- `commission_percentage`: `numeric|min:0|max:100`
- `minimum_withdrawal`: `numeric|min:0`

#### Response (Success)
```json
{
  "status": "success",
  "message": "Referral settings updated successfully",
  "data": {
    "commission_percentage": 5.00,
    "minimum_withdrawal": 1000.00
  }
}
```

#### Response (Error - Validation)
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "commission_percentage": ["The commission percentage must be between 0 and 100."]
  }
}
```

---

### 3. Get Referral List
**GET** `/api/admin/referral/list`

Get paginated list of users with their referral statistics.

#### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `search` | string | No | - | Search by name, email, or user code |
| `sort_by` | string | No | `created_at` | Sort field: `name`, `referral_count`, `total_earned`, `created_at` |
| `sort_order` | string | No | `desc` | Sort order: `asc` or `desc` |
| `per_page` | integer | No | 15 | Items per page |

#### Request Examples
```
GET /api/admin/referral/list
GET /api/admin/referral/list?search=john
GET /api/admin/referral/list?sort_by=referral_count&sort_order=desc
GET /api/admin/referral/list?per_page=20
GET /api/admin/referral/list?search=john&sort_by=total_earned&sort_order=desc&per_page=20
```

#### Response (Success)
```json
{
  "status": "success",
  "message": "Referral list retrieved successfully",
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Adewale Faizah",
        "email": "adewale@example.com",
        "user_code": "adewale123",
        "no_of_referral": 100,
        "amount_earned": "1100000.00",
        "date_joined": "05-07-25/07:22AM"
      },
      {
        "id": 2,
        "name": "John Adam",
        "email": "john@example.com",
        "user_code": "john456",
        "no_of_referral": 50,
        "amount_earned": "550000.00",
        "date_joined": "10-07-25/10:15AM"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 75
    }
  }
}
```

#### Response Fields
| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | User ID |
| `name` | string | Full name (first_name + sur_name) |
| `email` | string | User email |
| `user_code` | string | User's referral code |
| `no_of_referral` | integer | Number of users referred |
| `amount_earned` | string | Total referral earnings (formatted) |
| `date_joined` | string | Registration date (format: DD-MM-YY/HH:MMAM) |

---

### 4. Get User Referral Details
**GET** `/api/admin/referral/user/{userId}`

Get detailed referral information for a specific user.

#### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userId` | integer | Yes | User ID |

#### Response (Success)
```json
{
  "status": "success",
  "message": "User referral details retrieved successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "Adewale Faizah",
      "email": "adewale@example.com",
      "user_code": "adewale123",
      "referral_code_used": "referrer456",
      "referral_balance": "1100000.00"
    },
    "statistics": {
      "total_referrals": 100,
      "total_earned_from_referrals": "1100000.00",
      "date_joined": "05-07-25/07:22AM"
    },
    "referred_users": [
      {
        "id": 10,
        "name": "Referred User 1",
        "email": "referred1@example.com",
        "joined_at": "10-07-25/08:30AM"
      },
      {
        "id": 11,
        "name": "Referred User 2",
        "email": "referred2@example.com",
        "joined_at": "11-07-25/09:45AM"
      }
    ]
  }
}
```

#### Response (Error - Not Found)
```json
{
  "status": "error",
  "message": "Failed to retrieve user referral details: No query results for model [App\\Models\\User] {userId}"
}
```

---

## 🔍 Sorting Options

### Sort By Fields
- `name` - Sort by user's full name
- `referral_count` - Sort by number of referrals
- `total_earned` - Sort by total earnings
- `created_at` - Sort by registration date (default)

### Sort Order
- `asc` - Ascending order
- `desc` - Descending order (default)

---

## 🔎 Search Functionality

The search parameter searches across:
- First name
- Surname
- Email
- User code

**Example:**
```
GET /api/admin/referral/list?search=john
```
Will match users with:
- First name containing "john"
- Surname containing "john"
- Email containing "john"
- User code containing "john"

---

## 💡 Usage Examples

### Frontend Integration

#### React/Vue Example - Get Settings
```javascript
async function getReferralSettings() {
  try {
    const response = await fetch('/api/admin/referral/settings', {
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      console.log('Commission:', data.data.commission_percentage + '%');
      console.log('Min Withdrawal:', '₦' + data.data.minimum_withdrawal);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

#### React/Vue Example - Update Settings
```javascript
async function updateReferralSettings(commission, minWithdrawal) {
  try {
    const response = await fetch('/api/admin/referral/settings', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        commission_percentage: commission,
        minimum_withdrawal: minWithdrawal
      })
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      alert('Settings updated successfully!');
    } else {
      alert('Error: ' + data.message);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

#### React/Vue Example - Get Referral List
```javascript
async function getReferralList(search = '', sortBy = 'created_at', sortOrder = 'desc', page = 1) {
  try {
    const params = new URLSearchParams({
      search,
      sort_by: sortBy,
      sort_order: sortOrder,
      per_page: 15,
      page
    });
    
    const response = await fetch(`/api/admin/referral/list?${params}`, {
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      const { data: referrals, pagination } = data.data;
      
      // Display referrals
      referrals.forEach(referral => {
        console.log(`${referral.name}: ${referral.no_of_referral} referrals, ₦${referral.amount_earned}`);
      });
      
      // Handle pagination
      console.log(`Page ${pagination.current_page} of ${pagination.last_page}`);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

---

## ⚠️ Error Handling

### Common Error Responses

#### 422 Validation Error
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "commission_percentage": ["The commission percentage must be between 0 and 100."],
    "minimum_withdrawal": ["The minimum withdrawal must be at least 0."]
  }
}
```

#### 500 Server Error
```json
{
  "status": "error",
  "message": "Failed to retrieve referral list: [error details]"
}
```

#### 404 Not Found (User Details)
```json
{
  "status": "error",
  "message": "Failed to retrieve user referral details: No query results for model [App\\Models\\User] {userId}"
}
```

---

## 📊 Data Structure

### Referral Settings Table
```sql
referral_settings
  - id (primary key)
  - commission_percentage (decimal 5,2)
  - minimum_withdrawal (decimal 10,2)
  - created_at
  - updated_at
```

### User Referral Fields
- `refferal_code` - The referral code used when user registered (who referred them)
- `user_code` - User's own referral code (for others to use)

### Wallet Referral Balance
- `referral_balance` - Total referral earnings in user's wallet

---

## 🎯 Business Logic

### Commission Calculation
When a referred user makes a purchase or transaction:
1. Calculate commission: `transaction_amount × (commission_percentage / 100)`
2. Add to referrer's `wallet.referral_balance`
3. Track in transaction history

### Withdrawal Rules
- User can only withdraw if `referral_balance ≥ minimum_withdrawal`
- Withdrawal requests are processed by admin
- Balance is deducted upon approval

---

## ✅ Testing Checklist

- [ ] Get referral settings
- [ ] Update commission percentage only
- [ ] Update minimum withdrawal only
- [ ] Update both settings
- [ ] Validate commission percentage (0-100)
- [ ] Validate minimum withdrawal (≥ 0)
- [ ] Get referral list (default)
- [ ] Get referral list with search
- [ ] Get referral list with sorting
- [ ] Get referral list with pagination
- [ ] Get user referral details
- [ ] Handle invalid user ID
- [ ] Test with empty database

---

## 🔗 Related Endpoints

### User Referral (Public)
```
GET    /api/get-referral-details  # Get user's own referral balance
```

### Withdrawal (User)
```
POST   /api/withdraw               # Request withdrawal
GET    /api/withdraw/requests      # Get withdrawal requests
```

### Withdrawal Admin
```
GET    /api/withdraw/approve/{id}  # Approve withdrawal request
```

---

**End of Documentation**

