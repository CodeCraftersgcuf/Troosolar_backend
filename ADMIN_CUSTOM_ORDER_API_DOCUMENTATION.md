# Admin Custom Order API Documentation

**Last Updated:** 2024-12-27  
**Purpose:** Complete API documentation for Admin Custom Order Management System

---

## 📋 Overview

The Admin Custom Order system allows administrators to:
1. Create custom orders for users
2. Add products/bundles to user carts
3. Send email links to users
4. Manage user carts
5. Track order types (Buy Now or BNPL)

---

## 🔐 Authentication

All admin routes require:
- **Authentication:** `auth:sanctum` middleware
- **Role:** Admin or Super Admin
- **Headers:** 
  ```
  Authorization: Bearer {admin_token}
  Content-Type: application/json
  Accept: application/json
  ```

---

## 📍 API Endpoints

### 1. Create Custom Order
**POST** `/api/admin/cart/create-custom-order`

Creates a custom order by adding items to a user's cart and optionally sends an email link.

#### Request Body
```json
{
  "user_id": 123,
  "order_type": "buy_now",  // or "bnpl"
  "items": [
    {
      "type": "product",  // or "bundle"
      "id": 45,
      "quantity": 1
    },
    {
      "type": "bundle",
      "id": 12,
      "quantity": 2
    }
  ],
  "send_email": true,
  "email_message": "Custom message to user (optional)"
}
```

#### Request Fields
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `user_id` | integer | Yes | ID of the user |
| `order_type` | string | Yes | `buy_now` or `bnpl` |
| `items` | array | Yes | Array of items to add (min: 1) |
| `items[].type` | string | Yes | `product` or `bundle` |
| `items[].id` | integer | Yes | Product or Bundle ID |
| `items[].quantity` | integer | No | Quantity (default: 1) |
| `send_email` | boolean | No | Send email link (default: true) |
| `email_message` | string | No | Custom message in email (max: 1000 chars) |

#### Response (Success - 200)
```json
{
  "status": "success",
  "message": "Custom order created and added to user cart successfully",
  "data": {
    "user_id": 123,
    "user_name": "John Doe",
    "user_email": "john@example.com",
    "order_type": "buy_now",
    "items_added": 2,
    "cart_items": [
      {
        "id": 456,
        "type": "product",
        "quantity": 1,
        "unit_price": 250000,
        "subtotal": 250000
      },
      {
        "id": 457,
        "type": "bundle",
        "quantity": 2,
        "unit_price": 500000,
        "subtotal": 1000000
      }
    ],
    "cart_link": "https://yourdomain.com/cart?token=abc123...&type=buy_now",
    "email_sent": true
  }
}
```

#### Response (Error - 400)
```json
{
  "status": "error",
  "message": "Some items could not be added: Product ID 999 not found"
}
```

#### Response (Error - 422)
```json
{
  "status": "error",
  "message": "Validation failed",
  "errors": {
    "user_id": ["The user id field is required."],
    "order_type": ["The order type field is required."]
  }
}
```

---

### 2. Get Products/Bundles for Selection
**GET** `/api/admin/cart/products`

Retrieves all products and bundles that can be added to user carts.

#### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `category_id` | integer | No | Filter by category |
| `brand_id` | integer | No | Filter by brand |
| `type` | string | No | `all`, `products`, or `bundles` (default: `all`) |

#### Request Example
```
GET /api/admin/cart/products?category_id=5&type=products
```

#### Response (Success - 200)
```json
{
  "status": "success",
  "message": "Products and bundles retrieved successfully",
  "data": {
    "products": [
      {
        "id": 45,
        "type": "product",
        "title": "5KVA Inverter",
        "price": 250000,
        "discount_price": 230000,
        "category": "Inverters",
        "brand": "TrooSolar",
        "featured_image": "https://..."
      }
    ],
    "bundles": [
      {
        "id": 12,
        "type": "bundle",
        "title": "5KVA Solar System Bundle",
        "price": 2500000,
        "discount_price": 2300000,
        "bundle_type": "Residential",
        "featured_image": "https://..."
      }
    ]
  }
}
```

---

### 3. Get User's Cart
**GET** `/api/admin/cart/user/{userId}`

Retrieves the current cart contents for a specific user.

#### URL Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userId` | integer | Yes | User ID |

#### Request Example
```
GET /api/admin/cart/user/123
```

#### Response (Success - 200)
```json
{
  "status": "success",
  "message": "User cart retrieved successfully",
  "data": {
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "cart_items": [
      {
        "id": 456,
        "user_id": 123,
        "itemable_type": "App\\Models\\Product",
        "itemable_id": 45,
        "quantity": 1,
        "unit_price": 250000,
        "subtotal": 250000,
        "itemable": {
          "id": 45,
          "title": "5KVA Inverter",
          "price": 250000
        }
      }
    ],
    "total_items": 1,
    "total_amount": 250000
  }
}
```

---

### 4. Remove Item from User's Cart
**DELETE** `/api/admin/cart/user/{userId}/item/{itemId}`

Removes a specific item from a user's cart.

#### URL Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userId` | integer | Yes | User ID |
| `itemId` | integer | Yes | Cart Item ID |

#### Request Example
```
DELETE /api/admin/cart/user/123/item/456
```

#### Response (Success - 200)
```json
{
  "status": "success",
  "message": "Item removed from cart successfully",
  "data": null
}
```

---

### 5. Clear User's Cart
**DELETE** `/api/admin/cart/user/{userId}/clear`

Removes all items from a user's cart.

#### URL Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userId` | integer | Yes | User ID |

#### Request Example
```
DELETE /api/admin/cart/user/123/clear
```

#### Response (Success - 200)
```json
{
  "status": "success",
  "message": "User cart cleared successfully",
  "data": null
}
```

---

### 6. Resend Cart Link Email
**POST** `/api/admin/cart/resend-email/{userId}`

Resends the cart link email to a user.

#### URL Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `userId` | integer | Yes | User ID |

#### Request Body
```json
{
  "order_type": "buy_now",  // or "bnpl"
  "email_message": "Your items are ready for checkout (optional)"
}
```

#### Request Fields
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `order_type` | string | Yes | `buy_now` or `bnpl` |
| `email_message` | string | No | Custom message (max: 1000 chars) |

#### Request Example
```
POST /api/admin/cart/resend-email/123
```

#### Response (Success - 200)
```json
{
  "status": "success",
  "message": "Cart link email sent successfully",
  "data": {
    "email": "john@example.com",
    "cart_link": "https://yourdomain.com/cart?token=abc123...&type=buy_now"
  }
}
```

#### Response (Error - 400)
```json
{
  "status": "error",
  "message": "User cart is empty"
}
```

---

## 🔄 Complete Workflow

### Admin Creates Custom Order
```
1. Admin selects user
2. Admin selects products/bundles
3. Admin chooses order type (Buy Now or BNPL)
4. POST /api/admin/cart/create-custom-order
   ↓
5. Items added to user's cart
6. Email sent to user with cart link
7. User receives email and clicks link
8. User logs in (if not already)
9. User sees cart with admin-added items
10. User proceeds with checkout (Buy Now or BNPL flow)
```

### Email Link Format
```
https://yourdomain.com/cart?token={cart_access_token}&type={order_type}
```

The token is stored in the user's `cart_access_token` field and can be used to access the cart.

---

## 📧 Email Template

The email sent to users includes:
- Personalized greeting
- Custom admin message (if provided)
- List of cart items with details
- Total amount
- Button/link to access cart
- Information about Buy Now Pay Later (if BNPL)

---

## ⚠️ Error Handling

### Common Errors

#### 404 - User Not Found
```json
{
  "status": "error",
  "message": "User not found"
}
```

#### 404 - Item Not Found
```json
{
  "status": "error",
  "message": "Some items could not be added: Product ID 999 not found"
}
```

#### 400 - Empty Cart
```json
{
  "status": "error",
  "message": "User cart is empty"
}
```

#### 500 - Server Error
```json
{
  "status": "error",
  "message": "Failed to create custom order: [error details]"
}
```

---

## 🔒 Security Notes

1. **Authentication:** All endpoints require admin authentication
2. **Authorization:** Verify admin role before accessing endpoints
3. **Token Security:** Cart access tokens are randomly generated (64 characters)
4. **Email Validation:** Email is validated before sending
5. **User Verification:** User existence is verified before adding to cart

---

## 💡 Best Practices

1. **Always verify user exists** before creating custom orders
2. **Check product/bundle availability** before adding to cart
3. **Provide meaningful email messages** to improve user experience
4. **Monitor email delivery** - email failures don't block cart creation
5. **Use appropriate order type** - Buy Now for immediate payment, BNPL for installment plans
6. **Clear old tokens** periodically (optional - tokens are reusable by default)

---

## 📝 Integration Checklist

- [ ] Admin authentication working
- [ ] User lookup working
- [ ] Product/Bundle selection UI implemented
- [ ] Order type selection (Buy Now/BNPL)
- [ ] Email sending configured
- [ ] Cart link handling on frontend
- [ ] User login flow integrated
- [ ] Cart display on user frontend
- [ ] Checkout flow integrated

---

**End of Documentation**

