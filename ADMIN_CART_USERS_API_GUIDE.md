# Admin Cart Users API Guide

**Last Updated:** 2024-12-27  
**Purpose:** Guide for admin to view all users who have items in their carts

---

## 📍 Main Endpoint

### Get All Users With Carts
**GET** `/api/admin/cart/users-with-carts`

Returns a paginated list of all users who have items in their carts, including user details, cart statistics, and cart item details.

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
| `sort_by` | string | No | `last_cart_activity` | Sort field: `name`, `email`, `cart_item_count`, `total_cart_amount`, `last_cart_activity`, `created_at` |
| `sort_order` | string | No | `desc` | Sort order: `asc` or `desc` |
| `per_page` | integer | No | 15 | Items per page |

---

## 📋 Request Examples

```
GET /api/admin/cart/users-with-carts
GET /api/admin/cart/users-with-carts?search=john
GET /api/admin/cart/users-with-carts?sort_by=total_cart_amount&sort_order=desc
GET /api/admin/cart/users-with-carts?search=jane&sort_by=cart_item_count&per_page=20
```

---

## 📤 Response Format

### Success Response
```json
{
  "status": "success",
  "message": "Users with carts retrieved successfully",
  "data": {
    "data": [
      {
        "id": 5,
        "name": "John Doe",
        "email": "john.doe@example.com",
        "phone": "+2348012345678",
        "cart_item_count": 3,
        "total_cart_amount": "125000.00",
        "last_cart_activity": "2024-12-27 14:30:00",
        "last_cart_update": "2024-12-27 14:30:00",
        "user_created_at": "2024-12-20 10:00:00",
        "has_cart_access_token": true,
        "cart_items": [
          {
            "id": 10,
            "type": "product",
            "itemable_id": 45,
            "name": "5KVA Inverter",
            "quantity": 1,
            "unit_price": "50000.00",
            "subtotal": "50000.00"
          },
          {
            "id": 11,
            "type": "bundle",
            "itemable_id": 12,
            "name": "Solar System Bundle",
            "quantity": 2,
            "unit_price": "37500.00",
            "subtotal": "75000.00"
          }
        ]
      },
      {
        "id": 7,
        "name": "Jane Smith",
        "email": "jane.smith@example.com",
        "phone": "+2348098765432",
        "cart_item_count": 1,
        "total_cart_amount": "25000.00",
        "last_cart_activity": "2024-12-27 12:15:00",
        "last_cart_update": "2024-12-27 12:15:00",
        "user_created_at": "2024-12-25 08:30:00",
        "has_cart_access_token": false,
        "cart_items": [
          {
            "id": 15,
            "type": "product",
            "itemable_id": 50,
            "name": "Solar Battery",
            "quantity": 1,
            "unit_price": "25000.00",
            "subtotal": "25000.00"
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

### Cart Statistics
| Field | Type | Description |
|-------|------|-------------|
| `cart_item_count` | integer | Number of items in cart |
| `total_cart_amount` | string | Total cart value (formatted) |
| `last_cart_activity` | string | Last time item was added to cart |
| `last_cart_update` | string | Last time cart was updated |
| `has_cart_access_token` | boolean | Whether user has a cart access token (for custom orders) |

### Cart Items
| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | Cart item ID |
| `type` | string | Item type: `product` or `bundle` |
| `itemable_id` | integer | Product or Bundle ID |
| `name` | string | Product/Bundle name |
| `quantity` | integer | Quantity in cart |
| `unit_price` | string | Price per unit (formatted) |
| `subtotal` | string | Total for this item (formatted) |

---

## 🔍 Sorting Options

### Sort By Fields
- `name` - Sort by user's full name
- `email` - Sort by email address
- `cart_item_count` - Sort by number of cart items
- `total_cart_amount` - Sort by total cart value
- `last_cart_activity` - Sort by last cart activity (default)
- `created_at` - Sort by user registration date

### Sort Order
- `asc` - Ascending order
- `desc` - Descending order (default)

---

## 🔎 Search Functionality

The search parameter searches across:
- User's first name
- User's surname
- User's email
- User's phone number

**Example:**
```
GET /api/admin/cart/users-with-carts?search=john
```
Will match users with:
- First name containing "john"
- Surname containing "john"
- Email containing "john"
- Phone containing "john"

---

## 💡 Usage Examples

### Frontend Integration

#### React/Vue Example - Get Users With Carts
```javascript
async function getUsersWithCarts(filters = {}) {
  try {
    const params = new URLSearchParams();
    
    if (filters.search) params.append('search', filters.search);
    if (filters.sort_by) params.append('sort_by', filters.sort_by);
    if (filters.sort_order) params.append('sort_order', filters.sort_order);
    if (filters.per_page) params.append('per_page', filters.per_page);
    
    const response = await fetch(`/api/admin/cart/users-with-carts?${params}`, {
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
        console.log(`Cart Items: ${user.cart_item_count}`);
        console.log(`Total: ₦${user.total_cart_amount}`);
        console.log(`Has Token: ${user.has_cart_access_token ? 'Yes' : 'No'}`);
        
        // Display cart items
        user.cart_items.forEach(item => {
          console.log(`  - ${item.name}: ${item.quantity}x ₦${item.unit_price}`);
        });
      });
      
      // Handle pagination
      console.log(`Page ${pagination.current_page} of ${pagination.last_page}`);
      
      return { users, pagination };
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// Usage examples
getUsersWithCarts(); // Get all users with carts
getUsersWithCarts({ search: 'john' }); // Search for "john"
getUsersWithCarts({ 
  sort_by: 'total_cart_amount', 
  sort_order: 'desc' 
}); // Sort by highest cart value
getUsersWithCarts({ 
  search: 'jane',
  sort_by: 'cart_item_count',
  per_page: 20 
}); // Combined filters
```

---

## 🎯 Use Cases

### 1. View All Pending Custom Orders
Display all users who have items in their carts (potential custom orders).

### 2. Track Cart Activity
See which users have active carts and when they last updated them.

### 3. Identify High-Value Carts
Sort by `total_cart_amount` to find users with high-value carts.

### 4. Search for Specific User
Use search to quickly find a user's cart status.

### 5. Monitor Cart Access Tokens
Check `has_cart_access_token` to see if custom order emails were sent.

---

## ⚠️ Important Notes

### Cart Access Token
- `has_cart_access_token: true` - User has been sent a custom order email link
- `has_cart_access_token: false` - User added items themselves (regular cart)

### Empty Carts
This endpoint **only returns users who have items in their carts**. Users with empty carts are not included.

### Performance
- Query is optimized with batch loading to avoid N+1 problems
- Cart items are loaded efficiently for all users at once
- Suitable for large datasets with pagination

---

## 🔗 Related Endpoints

### Individual User Cart
```
GET    /api/admin/cart/user/{userId}  # Get specific user's cart details
```

### Custom Order Management
```
POST   /api/admin/cart/create-custom-order  # Add items to user's cart
POST   /api/admin/cart/resend-email/{userId}  # Resend cart link email
```

### Cart Management
```
DELETE /api/admin/cart/user/{userId}/item/{itemId}  # Remove item from cart
DELETE /api/admin/cart/user/{userId}/clear  # Clear user's cart
```

---

## ✅ Testing Checklist

- [ ] Get all users with carts (default)
- [ ] Search by name
- [ ] Search by email
- [ ] Search by phone
- [ ] Sort by name (ascending)
- [ ] Sort by cart_item_count (descending)
- [ ] Sort by total_cart_amount (descending)
- [ ] Sort by last_cart_activity (default)
- [ ] Test pagination
- [ ] Verify cart_items are included
- [ ] Verify has_cart_access_token is accurate
- [ ] Test with users who have no carts (should return empty)

---

**End of Documentation**

