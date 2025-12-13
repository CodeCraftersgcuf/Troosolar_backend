# User Custom Order Flow - Frontend Integration Guide

**Last Updated:** 2024-12-27  
**Purpose:** Complete guide for frontend integration of user-facing custom order flow

---

## 📋 Overview

When an admin creates a custom order, users receive an email with a cart link. This document explains how to handle the user flow on the frontend.

---

## 🔗 Email Link Handling

### Link Format
```
https://yourdomain.com/cart?token={cart_access_token}&type={order_type}
```

### Parameters
- `token` - Cart access token (64 characters)
- `type` - Order type: `buy_now` or `bnpl`

---

## 🛣️ User Flow Steps

### Step 1: User Clicks Email Link

When user clicks the link:
```
https://yourdomain.com/cart?token=abc123...&type=buy_now
```

### Step 2: Frontend Reads Parameters

Extract `token` and `type` from URL query parameters.

### Step 3: Verify Cart Access

**API Call:** `GET /api/cart/access/{token}`

#### Request
```
GET /api/cart/access/abc123...
Headers:
  Authorization: Bearer {user_token} (optional - if user is logged in)
```

#### Response (Success - 200)
```json
{
  "status": "success",
  "message": "Cart accessed successfully",
  "data": {
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "cart_items": [
      {
        "id": 456,
        "itemable_type": "App\\Models\\Product",
        "itemable_id": 45,
        "quantity": 1,
        "unit_price": 250000,
        "subtotal": 250000,
        "itemable": {
          "id": 45,
          "title": "5KVA Inverter",
          "price": 250000,
          "discount_price": 230000
        }
      }
    ],
    "requires_login": false,  // true if user not logged in or different user
    "message": "Cart accessed successfully"
  }
}
```

#### Response (Error - 404)
```json
{
  "status": "error",
  "message": "Invalid or expired cart link"
}
```

---

## 🔐 Authentication Flow

### Case 1: User is NOT Logged In
1. Display message: "Please login to access your cart"
2. Redirect to login page with return URL: `/cart?token={token}&type={type}`
3. After login, redirect back to cart page

### Case 2: User is Logged In (Different User)
1. Display message: "This cart belongs to another user. Please login with the correct account."
2. Option to logout and login with correct account

### Case 3: User is Logged In (Correct User)
1. Display cart items
2. Proceed to checkout options

---

## 🛒 Cart Display

After successful cart access, display:
- User name and email
- List of cart items with:
  - Product/Bundle name
  - Quantity
  - Unit price
  - Subtotal
  - Product image (if available)
- Total amount
- Order type indicator (Buy Now or BNPL)

---

## 📍 Checkout Flow Based on Order Type

### If `type=buy_now` (Buy Now Flow)

Proceed with Buy Now checkout flow:

#### Step 1: Customer Type Selection
```
Options:
- Residential
- SME
- Commercial
```

#### Step 2: Product Category Selection
```
Options:
- Solar Panels, Inverter and Battery Solution
- Inverter and Battery Solution
- Battery Only (choose battery capacity)
- Inverter Only (choose inverter capacity)
- Solar Panels Only
```

**For "Solar Panels, Inverter and Battery Solution" or "Inverter and Battery Solution":**

#### Step 3: Method Selection
```
Options:
1. Request a Professional Audit (Paid)
   ↓
   Options:
   - Home/Office
   - Commercial/Industrial
   
   If "Home/Office":
   - Submit: Location/Address (State, House No, Landmark, Street Name)
   - Submit: Number of floors and rooms
   - System generates invoice based on location, address, floors, rooms
   - Message: "You will be contacted for inspection within 24 hours"
   
   If "Commercial/Industrial":
   - Display: "Our team will contact you within 24-48 hours to discuss your energy audit"
   - Admin receives notification

2. Choose My Solar System
   - Sets mock price ₦2,500,000
   - Proceed to checkout

3. Build My System
   - Shows "Under construction" alert
```

#### Step 4: Checkout Options
- Installation Preference (TrooSolar Installer / Own Installer)
- Additional Services (Insurance, Inspection)
- Add-ons selection

#### Step 5: Invoice & Payment
- **API:** `POST /api/orders/checkout`
- Review invoice
- Proceed to payment

#### Step 6: Payment Confirmation
- **API:** `POST /api/order/payment-confirmation`
- Payment gateway integration

#### Step 7: Calendar Booking
- **API:** `GET /api/calendar/slots?type=installation&payment_date={date}`
- Book installation date

---

### If `type=bnpl` (Buy Now Pay Later Flow)

Proceed with BNPL checkout flow:

#### Step 1: Customer Type Selection
Same as Buy Now (Residential, SME, Commercial)

#### Step 2: Product Category Selection
Same as Buy Now

#### Step 3: Method Selection (if applicable)
Same as Buy Now with audit options

#### Step 4: Loan Calculator
- Configure deposit % (30-80%, default: 30%)
- Select tenor (3, 6, 9, or 12 months)
- View calculated amounts:
  - Deposit amount
  - Principal
  - Total interest
  - Monthly repayment
  - Total repayment

#### Step 5: Create Loan Calculation
- **API:** `POST /api/loan-calculation`
- Validates minimum loan amount (₦1,500,000)

#### Step 6: Submit BNPL Application
- **API:** `POST /api/bnpl/apply`
- Fill personal details (name, BVN, phone, email, social media)
- Fill property details (if applicable)
- Upload bank statement
- Upload live photo

#### Step 7: Admin Review
- Application status: `pending` → `approved/rejected/counter_offer`
- **API:** `GET /api/bnpl/status/{application_id}`

#### Step 8: Guarantor (if approved)
- **API:** `POST /api/bnpl/guarantor/invite`
- **API:** `POST /api/bnpl/guarantor/upload`

#### Step 9: Calendar Booking
- **API:** `GET /api/calendar/slots?type=installation`

---

## 🔄 Complete User Journey

```
1. User receives email
   ↓
2. Clicks cart link
   ↓
3. Frontend reads token and type from URL
   ↓
4. GET /api/cart/access/{token}
   ↓
5. Check if user is logged in
   ├─ No → Redirect to login
   └─ Yes → Continue
   ↓
6. Display cart items
   ↓
7. User selects order type flow
   ├─ Buy Now → Buy Now Flow
   └─ BNPL → BNPL Flow
   ↓
8. Complete checkout process
```

---

## 📱 Frontend Implementation Checklist

### URL Handling
- [ ] Extract `token` from query parameter
- [ ] Extract `type` from query parameter
- [ ] Store token in state/localStorage
- [ ] Handle invalid/missing parameters

### Authentication
- [ ] Check if user is logged in
- [ ] Verify user matches cart owner
- [ ] Handle login redirect with return URL
- [ ] Handle logout/login flow

### Cart Display
- [ ] Call `GET /api/cart/access/{token}`
- [ ] Display cart items
- [ ] Show order type (Buy Now/BNPL)
- [ ] Display total amount
- [ ] Show user information

### Navigation
- [ ] Buy Now button → Buy Now flow
- [ ] BNPL button → BNPL flow
- [ ] Continue shopping option
- [ ] Remove items option (if needed)

### Error Handling
- [ ] Invalid token → Show error message
- [ ] Expired token → Show error message
- [ ] Network errors → Retry mechanism
- [ ] Empty cart → Show empty state

---

## 🎨 UI/UX Recommendations

### Cart Page
```
┌─────────────────────────────────┐
│  TrooSolar - Your Cart          │
├─────────────────────────────────┤
│  Items Prepared by Admin         │
│                                  │
│  [Product Image]                 │
│  5KVA Inverter                  │
│  Quantity: 1                     │
│  Price: ₦250,000                 │
│  ─────────────────────────────   │
│  Total: ₦250,000                 │
│                                  │
│  [Proceed with Buy Now]          │
│  [Proceed with BNPL]             │
└─────────────────────────────────┘
```

### Login Required State
```
┌─────────────────────────────────┐
│  Login Required                 │
│                                  │
│  Please login to access your     │
│  cart items.                     │
│                                  │
│  [Login] [Register]              │
└─────────────────────────────────┘
```

---

## 🔧 Technical Implementation

### React/Vue Example

```javascript
// Extract token and type from URL
const urlParams = new URLSearchParams(window.location.search);
const token = urlParams.get('token');
const type = urlParams.get('type'); // 'buy_now' or 'bnpl'

// Verify cart access
async function verifyCartAccess() {
  try {
    const response = await fetch(`/api/cart/access/${token}`, {
      headers: {
        'Authorization': `Bearer ${userToken}`, // if logged in
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      if (data.data.requires_login) {
        // Redirect to login with return URL
        window.location.href = `/login?return=/cart?token=${token}&type=${type}`;
      } else {
        // Display cart items
        setCartItems(data.data.cart_items);
        setOrderType(type);
      }
    }
  } catch (error) {
    // Handle error
  }
}

// Navigate to checkout
function proceedToCheckout(orderType) {
  if (orderType === 'buy_now') {
    // Navigate to Buy Now flow
    navigate('/buy-now?from=custom-order');
  } else if (orderType === 'bnpl') {
    // Navigate to BNPL flow
    navigate('/bnpl?from=custom-order');
  }
}
```

---

## 📋 API Endpoints Summary

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/cart/access/{token}` | GET | Verify cart access token |
| `/api/cart` | GET | Get user's cart (after login) |
| `/api/orders/checkout` | POST | Create order (Buy Now) |
| `/api/loan-calculation` | POST | Create loan calculation (BNPL) |
| `/api/bnpl/apply` | POST | Submit BNPL application |
| `/api/order/payment-confirmation` | POST | Confirm payment |
| `/api/calendar/slots` | GET | Get available slots |

---

## ⚠️ Important Notes

1. **Token Validity:** Tokens are reusable by default. To make them single-use, uncomment the token clearing code in the controller.

2. **User Matching:** Always verify the logged-in user matches the cart owner.

3. **Order Type:** The `type` parameter determines which checkout flow to use.

4. **Email Link:** The link should be secure and not easily guessable.

5. **Error Messages:** Provide clear error messages for expired/invalid tokens.

---

## 🎯 Integration Flow Diagram

```
Email Link
    ↓
[Frontend] Extract token & type
    ↓
GET /api/cart/access/{token}
    ↓
User Logged In?
    ├─ No → Login → Return to cart
    └─ Yes → Continue
    ↓
Display Cart Items
    ↓
User Selects Flow
    ├─ Buy Now → Buy Now Checkout
    └─ BNPL → BNPL Application Flow
```

---

**End of Documentation**

