# BNPL Repayment Routes - Complete Frontend Guide
**Last Updated:** 2024-12-27  
**Purpose:** Complete reference for all BNPL repayment-related API endpoints for frontend integration

---

## 📑 Table of Contents

1. [Overview](#overview)
2. [Repayment Routes](#repayment-routes)
3. [Request/Response Formats](#requestresponse-formats)
4. [Complete Payment Flow](#complete-payment-flow)
5. [Integration Examples](#integration-examples)
6. [Error Handling](#error-handling)
7. [Best Practices](#best-practices)

---

## 🎯 Overview

This guide covers all routes related to BNPL (Buy Now Pay Later) repayments. Users can
- View their BNPL orders
- Check repayment schedules
- View installments (current and history)
- Pay installments using multiple payment methods
- Track payment history

---

## 🛣️ Repayment Routes

### 1. Get All BNPL Orders
**Endpoint:** `GET /api/bnpl/orders`

**Description:** Get all BNPL orders for the authenticated user with loan summary.

**Query Parameters:**
- `status` (optional): Filter by order status (`pending`, `confirmed`, `processing`, `shipped`, `delivered`, `cancelled`)
- `per_page` (optional): Items per page (default: 15)
- `page` (optional): Page number

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Example Request:**
```http
GET /api/bnpl/orders?status=pending&per_page=10&page=1
Authorization: Bearer 1|xxxxxxxxxxxxx
```

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "data": [
      {
        "id": 123,
        "order_number": "ORD123456",
        "order_status": "confirmed",
        "payment_status": "confirmed",
        "total_price": 2750000.00,
        "items": [
          {
            "id": 1,
            "itemable_type": "product",
            "itemable_id": 45,
            "quantity": 1,
            "unit_price": 2750000.00,
            "subtotal": 2750000.00,
            "item": {
              "id": 45,
              "title": "Solar System Package"
            }
          }
        ],
        "delivery_address": {
          "id": 10,
          "address": "123 Main St",
          "state": "Lagos"
        },
        "loan_summary": {
          "total_installments": 12,
          "paid_installments": 3,
          "pending_installments": 9,
          "next_payment_date": "2024-02-15",
          "next_payment_amount": 250000.00
        },
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 10,
      "total": 25,
      "from": 1,
      "to": 10
    }
  },
  "message": "BNPL orders retrieved successfully"
}
```

---

### 2. Get Single BNPL Order with Full Repayment Details
**Endpoint:** `GET /api/bnpl/orders/{order_id}`

**Description:** Get complete BNPL order details including full repayment schedule, summary, and history.

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Example Request:**
```http
GET /api/bnpl/orders/123
Authorization: Bearer 1|xxxxxxxxxxxxx
```

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "id": 123,
    "order_number": "ORD123456",
    "order_status": "confirmed",
    "payment_status": "confirmed",
    "total_price": 2750000.00,
    "items": [...],
    "delivery_address": {...},
    "loan_application": {
      "id": 45,
      "status": "approved",
      "loan_amount": 2750000.00,
      "repayment_duration": 12,
      "guarantor": {
        "id": 10,
        "full_name": "John Doe",
        "status": "approved"
      }
    },
    "repayment_schedule": [
      {
        "id": 1,
        "installment_number": 1,
        "amount": 250000.00,
        "payment_date": "2024-02-15",
        "status": "paid",
        "paid_at": "2024-02-10 14:30:00",
        "is_overdue": false,
        "transaction": {
          "id": 789,
          "tx_id": "TXN123456789",
          "method": "card",
          "amount": 250000.00,
          "transacted_at": "2024-02-10 14:30:00"
        }
      },
      {
        "id": 2,
        "installment_number": 2,
        "amount": 250000.00,
        "payment_date": "2024-03-15",
        "status": "paid",
        "paid_at": "2024-03-12 10:15:00",
        "is_overdue": false,
        "transaction": {...}
      },
      {
        "id": 3,
        "installment_number": 3,
        "amount": 250000.00,
        "payment_date": "2024-04-15",
        "status": "pending",
        "paid_at": null,
        "is_overdue": false,
        "transaction": null
      }
    ],
    "repayment_summary": {
      "total_installments": 12,
      "paid_installments": 2,
      "pending_installments": 10,
      "overdue_installments": 0,
      "total_amount": 3000000.00,
      "paid_amount": 500000.00,
      "pending_amount": 2500000.00
    },
    "repayment_history": [
      {
        "id": 1,
        "amount": 250000.00,
        "status": "completed",
        "created_at": "2024-02-10 14:30:00"
      },
      {
        "id": 2,
        "amount": 250000.00,
        "status": "completed",
        "created_at": "2024-03-12 10:15:00"
      }
    ],
    "loan_details": {
      "loan_amount": 2750000.00,
      "down_payment": 825000.00,
      "total_amount": 3000000.00,
      "repayment_duration": 12,
      "interest_rate": 4.0
    },
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  },
  "message": "BNPL order details retrieved successfully"
}
```

---

### 3. Get Repayment Schedule by Application
**Endpoint:** `GET /api/bnpl/applications/{application_id}/repayment-schedule`

**Description:** Get complete repayment schedule for a specific BNPL application.

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Example Request:**
```http
GET /api/bnpl/applications/45/repayment-schedule
Authorization: Bearer 1|xxxxxxxxxxxxx
```

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "application_id": 45,
    "loan_amount": 2750000.00,
    "repayment_duration": 12,
    "schedule": [
      {
        "id": 1,
        "installment_number": 1,
        "amount": 250000.00,
        "payment_date": "2024-02-15",
        "status": "paid",
        "paid_at": "2024-02-10 14:30:00",
        "is_overdue": false,
        "days_until_due": null,
        "transaction": {
          "id": 789,
          "tx_id": "TXN123456789",
          "method": "card",
          "amount": 250000.00,
          "transacted_at": "2024-02-10 14:30:00"
        }
      },
      {
        "id": 2,
        "installment_number": 2,
        "amount": 250000.00,
        "payment_date": "2024-03-15",
        "status": "pending",
        "paid_at": null,
        "is_overdue": false,
        "days_until_due": 25,
        "transaction": null
      },
      {
        "id": 3,
        "installment_number": 3,
        "amount": 250000.00,
        "payment_date": "2024-04-15",
        "status": "pending",
        "paid_at": null,
        "is_overdue": false,
        "days_until_due": 56,
        "transaction": null
      }
    ],
    "summary": {
      "total_installments": 12,
      "paid_installments": 1,
      "pending_installments": 11,
      "overdue_installments": 0,
      "total_amount": 3000000.00,
      "paid_amount": 250000.00,
      "pending_amount": 2750000.00
    }
  },
  "message": "Repayment schedule retrieved successfully"
}
```

---

### 4. Get Installments with History
**Endpoint:** `GET /api/installments/with-history`

**Description:** Get current month installments and historical installments with overdue information.

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Example Request:**
```http
GET /api/installments/with-history
Authorization: Bearer 1|xxxxxxxxxxxxx
```

**Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "current_month": [
      {
        "id": 3,
        "mono_calculation_id": 45,
        "amount": 250000.00,
        "payment_date": "2024-01-20",
        "status": "pending",
        "computed_status": "pending",
        "paid_at": null,
        "remaining_duration": 10,
        "is_overdue": false,
        "transaction": null
      }
    ],
    "history": [
      {
        "id": 1,
        "mono_calculation_id": 45,
        "amount": 250000.00,
        "payment_date": "2023-12-15",
        "status": "paid",
        "computed_status": "paid",
        "paid_at": "2023-12-10 14:30:00",
        "remaining_duration": 11,
        "is_overdue": false,
        "transaction": {
          "id": 789,
          "tx_id": "TXN123456789",
          "method": "card",
          "type": "debit",
          "status": "success",
          "amount": 250000.00,
          "reference": "INSTALLMENT#1",
          "transacted_at": "2023-12-10 14:30:00"
        }
      }
    ],
    "isActive": true,
    "isCompleted": false,
    "hasOverdue": false,
    "overdueCount": 0,
    "overdueAmount": 0.00,
    "loan": {
      "id": 45,
      "loan_amount": 2750000.00,
      "repayment_duration": 12,
      "down_payment": 825000.00,
      "total_amount": 3000000.00,
      "interest_rate": 4.0
    }
  }
}
```

---

### 5. Pay Installment
**Endpoint:** `POST /api/installments/{installmentId}/pay`

**Description:** Pay a single installment using wallet or payment gateway.

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

**Request Body:**

**For Wallet Payment (Shop Balance):**
```json
{
  "method": "wallet",
  "type": "shop",
  "reference": "INSTALLMENT#123" // optional
}
```

**For Wallet Payment (Loan Balance):**
```json
{
  "method": "wallet",
  "type": "loan",
  "reference": "INSTALLMENT#123" // optional
}
```

**For Bank/Card/Transfer Payment:**
```json
{
  "method": "bank", // or "card" or "transfer"
  "tx_id": "TXN123456789", // Required: from payment gateway
  "reference": "INSTALLMENT#123", // optional
  "title": "Loan Installment Payment" // optional
}
```

**Example Request:**
```http
POST /api/installments/3/pay
Authorization: Bearer 1|xxxxxxxxxxxxx
Content-Type: application/json

{
  "method": "wallet",
  "type": "shop",
  "reference": "INSTALLMENT#3"
}
```

**Response (200 OK):**
```json
{
  "status": "success",
  "message": "Installment paid successfully",
  "data": {
    "id": 3,
    "mono_calculation_id": 45,
    "amount": 250000.00,
    "payment_date": "2024-01-20",
    "status": "paid",
    "computed_status": "paid",
    "paid_at": "2024-01-15 14:30:00",
    "remaining_duration": 9,
    "transaction": {
      "id": 790,
      "tx_id": "WALLET-SHOP-1705327800-3",
      "method": "wallet",
      "type": "debit",
      "status": "success",
      "amount": 250000.00,
      "reference": "INSTALLMENT#3",
      "transacted_at": "2024-01-15 14:30:00"
    }
  }
}
```

---

## 📊 Request/Response Formats

### Payment Methods

| Method | Type Required | tx_id Required | Description |
|--------|--------------|----------------|-------------|
| `wallet` | Yes (`shop` or `loan`) | No | Direct deduction from wallet |
| `bank` | No | Yes | Bank transfer via gateway |
| `card` | No | Yes | Card payment via gateway |
| `transfer` | No | Yes | Bank transfer via gateway |

### Installment Status Values

- `pending` - Not yet paid, not yet due
- `paid` - Successfully paid
- `overdue` - Not paid, past due date (computed)

### Order Status Values

- `pending` - Order placed, awaiting payment
- `confirmed` - Payment confirmed
- `processing` - Being processed
- `shipped` - Shipped
- `delivered` - Delivered
- `cancelled` - Cancelled

---

## 🔄 Complete Payment Flow

### Step-by-Step Flow

```
1. User Views BNPL Orders
   GET /api/bnpl/orders
   ↓
2. User Selects Order
   GET /api/bnpl/orders/{order_id}
   ↓
3. View Repayment Schedule
   - See all installments
   - Check which are pending/overdue
   - See next payment date
   ↓
4. User Selects Installment to Pay
   - Get installment ID
   - Check amount and due date
   - Verify status is "pending" or "overdue"
   ↓
5. User Chooses Payment Method
   ├─ Wallet Payment
   │  ├─ Check balance (GET /api/loan-wallet)
   │  ├─ Verify sufficient balance
   │  └─ POST /api/installments/{id}/pay (method: wallet, type: shop/loan)
   │
   └─ Gateway Payment
      ├─ Initialize payment gateway
      ├─ User completes payment
      ├─ Gateway returns tx_id
      └─ POST /api/installments/{id}/pay (method: bank/card/transfer, tx_id: ...)
   ↓
6. Payment Processed
   - Installment status → "paid"
   - Transaction created
   - Wallet updated (if wallet payment)
   ↓
7. Refresh Repayment Schedule
   GET /api/bnpl/orders/{order_id}
   - See updated status
   - View transaction details
```

---

## 💻 Integration Examples

### React/JavaScript Example

```javascript
// 1. Get BNPL Orders
async function getBnplOrders(status = null, page = 1) {
  const params = new URLSearchParams({ page });
  if (status) params.append('status', status);
  
  const response = await fetch(`/api/bnpl/orders?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// 2. Get Order Details with Repayment Schedule
async function getOrderDetails(orderId) {
  const response = await fetch(`/api/bnpl/orders/${orderId}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// 3. Pay Installment with Wallet
async function payWithWallet(installmentId, walletType = 'shop') {
  const response = await fetch(`/api/installments/${installmentId}/pay`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      method: 'wallet',
      type: walletType,
      reference: `INSTALLMENT#${installmentId}`
    })
  });
  
  return await response.json();
}

// 4. Pay Installment with Payment Gateway
async function payWithGateway(installmentId, txId, method = 'card') {
  const response = await fetch(`/api/installments/${installmentId}/pay`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      method: method,
      tx_id: txId,
      reference: `INSTALLMENT#${installmentId}`,
      title: 'Loan Installment Payment'
    })
  });
  
  return await response.json();
}

// 5. Get Installments with History
async function getInstallmentsWithHistory() {
  const response = await fetch('/api/installments/with-history', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}
```

### Flutter/Dart Example

```dart
// 1. Get BNPL Orders
Future<Map<String, dynamic>> getBnplOrders({
  String? status,
  int page = 1,
  int perPage = 15,
}) async {
  final queryParams = {
    'page': page.toString(),
    'per_page': perPage.toString(),
  };
  if (status != null) queryParams['status'] = status;
  
  final uri = Uri.parse('$baseUrl/api/bnpl/orders')
      .replace(queryParameters: queryParams);
  
  final response = await http.get(
    uri,
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );
  
  if (response.statusCode == 200) {
    return json.decode(response.body);
  }
  throw Exception('Failed to load BNPL orders');
}

// 2. Get Order Details
Future<Map<String, dynamic>> getOrderDetails(int orderId) async {
  final response = await http.get(
    Uri.parse('$baseUrl/api/bnpl/orders/$orderId'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );
  
  if (response.statusCode == 200) {
    return json.decode(response.body);
  }
  throw Exception('Failed to load order details');
}

// 3. Pay with Wallet
Future<Map<String, dynamic>> payWithWallet({
  required int installmentId,
  required String walletType, // 'shop' or 'loan'
}) async {
  final response = await http.post(
    Uri.parse('$baseUrl/api/installments/$installmentId/pay'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: json.encode({
      'method': 'wallet',
      'type': walletType,
      'reference': 'INSTALLMENT#$installmentId',
    }),
  );
  
  if (response.statusCode == 200) {
    return json.decode(response.body);
  } else {
    final error = json.decode(response.body);
    throw Exception(error['message'] ?? 'Payment failed');
  }
}

// 4. Pay with Gateway
Future<Map<String, dynamic>> payWithGateway({
  required int installmentId,
  required String txId,
  required String method, // 'bank', 'card', or 'transfer'
}) async {
  final response = await http.post(
    Uri.parse('$baseUrl/api/installments/$installmentId/pay'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: json.encode({
      'method': method,
      'tx_id': txId,
      'reference': 'INSTALLMENT#$installmentId',
      'title': 'Loan Installment Payment',
    }),
  );
  
  if (response.statusCode == 200) {
    return json.decode(response.body);
  } else {
    final error = json.decode(response.body);
    throw Exception(error['message'] ?? 'Payment failed');
  }
}

// 5. Get Installments with History
Future<Map<String, dynamic>> getInstallmentsWithHistory() async {
  final response = await http.get(
    Uri.parse('$baseUrl/api/installments/with-history'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );
  
  if (response.statusCode == 200) {
    return json.decode(response.body);
  }
  throw Exception('Failed to load installments');
}
```

---

## ⚠️ Error Handling

### Common Errors

#### 1. Insufficient Balance (Wallet Payment)
```json
{
  "status": "error",
  "message": "Insufficient shop balance",
  "errors": {
    "shop_balance": ["Insufficient balance"]
  }
}
```
**HTTP Status:** 422

**Solution:** Check wallet balance before payment, show error to user.

#### 2. Already Paid
```json
{
  "status": "success",
  "message": "Installment already paid",
  "data": {
    "id": 3,
    "status": "paid",
    ...
  }
}
```
**HTTP Status:** 200

**Solution:** Check status before showing payment button.

#### 3. Missing Transaction ID
```json
{
  "status": "error",
  "message": "Validation failed.",
  "errors": {
    "tx_id": ["tx_id is required for non-wallet methods"]
  }
}
```
**HTTP Status:** 422

**Solution:** Ensure payment gateway returns tx_id before calling endpoint.

#### 4. Installment Not Found
```json
{
  "status": "error",
  "message": "No query results for model [App\\Models\\LoanInstallment]"
}
```
**HTTP Status:** 404

**Solution:** Verify installment ID exists and belongs to user.

#### 5. Unauthorized Access
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```
**HTTP Status:** 401

**Solution:** Check authentication token is valid and not expired.

---

## ✅ Best Practices

### 1. Always Check Balance First
```javascript
// Before wallet payment
const wallet = await getWalletBalance();
const availableBalance = walletType === 'shop' 
  ? wallet.data.shop_balance 
  : wallet.data.loan_balance;

if (availableBalance < installmentAmount) {
  showError('Insufficient balance');
  return;
}
```

### 2. Verify Installment Status
```javascript
// Before showing payment button
if (installment.status === 'paid') {
  showMessage('This installment is already paid');
  disablePaymentButton();
}
```

### 3. Handle Payment Gateway Errors
```javascript
try {
  // Initialize payment gateway
  const gatewayResponse = await initializePaymentGateway(amount);
  
  // User completes payment
  const txId = gatewayResponse.transaction_id;
  
  // Confirm with backend
  const result = await payWithGateway(installmentId, txId);
  
  if (result.status === 'success') {
    showSuccess('Payment successful!');
    refreshRepaymentSchedule();
  }
} catch (error) {
  showError(`Payment failed: ${error.message}`);
  // Allow retry
}
```

### 4. Refresh After Payment
```javascript
// After successful payment
async function handlePaymentSuccess(installmentId) {
  // Refresh repayment schedule
  const orderDetails = await getOrderDetails(orderId);
  
  // Update UI
  updateRepaymentSchedule(orderDetails.data.repayment_schedule);
  updateSummary(orderDetails.data.repayment_summary);
}
```

### 5. Show Loading States
```javascript
async function payInstallment(installmentId) {
  setLoading(true);
  disablePaymentButton();
  
  try {
    const result = await payWithWallet(installmentId, 'shop');
    // Handle success
  } catch (error) {
    // Handle error
  } finally {
    setLoading(false);
    enablePaymentButton();
  }
}
```

### 6. Store Transaction References
```javascript
// Save transaction details for reference
localStorage.setItem(
  `payment_${installmentId}`,
  JSON.stringify({
    tx_id: transaction.tx_id,
    amount: transaction.amount,
    date: transaction.transacted_at,
    method: transaction.method
  })
);
```

### 7. Error Recovery
```javascript
// Provide clear error messages
function handlePaymentError(error) {
  let message = 'Payment failed';
  
  if (error.errors) {
    if (error.errors.shop_balance) {
      message = 'Insufficient shop balance. Please fund your wallet.';
    } else if (error.errors.tx_id) {
      message = 'Transaction ID is required. Please try again.';
    }
  } else if (error.message) {
    message = error.message;
  }
  
  showError(message);
  enableRetryButton();
}
```

---

## 📱 Complete Payment Component Example

### React Component

```jsx
import React, { useState, useEffect } from 'react';

function InstallmentPayment({ installment, orderId, onPaymentSuccess }) {
  const [paymentMethod, setPaymentMethod] = useState('wallet');
  const [walletType, setWalletType] = useState('shop');
  const [walletBalance, setWalletBalance] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  useEffect(() => {
    // Load wallet balance
    loadWalletBalance();
  }, [walletType]);

  const loadWalletBalance = async () => {
    try {
      const response = await fetch('/api/loan-wallet', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      });
      const data = await response.json();
      setWalletBalance(data.data);
    } catch (err) {
      console.error('Failed to load wallet:', err);
    }
  };

  const handlePayment = async () => {
    setLoading(true);
    setError(null);

    try {
      let result;
      
      if (paymentMethod === 'wallet') {
        // Check balance
        const balance = walletType === 'shop' 
          ? walletBalance?.shop_balance 
          : walletBalance?.loan_balance;
          
        if (balance < installment.amount) {
          setError(`Insufficient ${walletType} balance`);
          setLoading(false);
          return;
        }
        
        result = await payWithWallet(installment.id, walletType);
      } else {
        // Initialize payment gateway
        const txId = await initializePaymentGateway({
          amount: installment.amount,
          method: paymentMethod
        });
        
        result = await payWithGateway(installment.id, txId, paymentMethod);
      }

      if (result.status === 'success') {
        onPaymentSuccess();
        // Refresh order details
        window.location.reload();
      }
    } catch (err) {
      setError(err.message || 'Payment failed');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="installment-payment">
      <h3>Pay Installment</h3>
      
      <div className="installment-details">
        <p>Amount: ₦{installment.amount.toLocaleString()}</p>
        <p>Due Date: {installment.payment_date}</p>
        {installment.is_overdue && (
          <p className="overdue">⚠️ This installment is overdue</p>
        )}
      </div>

      <div className="payment-method">
        <label>Payment Method:</label>
        <select 
          value={paymentMethod} 
          onChange={(e) => setPaymentMethod(e.target.value)}
        >
          <option value="wallet">Wallet</option>
          <option value="card">Card</option>
          <option value="bank">Bank Transfer</option>
        </select>
      </div>

      {paymentMethod === 'wallet' && (
        <div className="wallet-type">
          <label>Wallet Type:</label>
          <select 
            value={walletType} 
            onChange={(e) => setWalletType(e.target.value)}
          >
            <option value="shop">
              Shop Balance (₦{walletBalance?.shop_balance?.toLocaleString() || 0})
            </option>
            <option value="loan">
              Loan Balance (₦{walletBalance?.loan_balance?.toLocaleString() || 0})
            </option>
          </select>
        </div>
      )}

      {error && <div className="error">{error}</div>}

      <button 
        onClick={handlePayment} 
        disabled={loading || installment.status === 'paid'}
      >
        {loading ? 'Processing...' : 'Pay Installment'}
      </button>
    </div>
  );
}

export default InstallmentPayment;
```

---

## 🔍 Quick Reference

### Route Summary

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/bnpl/orders` | List all BNPL orders |
| GET | `/api/bnpl/orders/{id}` | Get order with repayment details |
| GET | `/api/bnpl/applications/{id}/repayment-schedule` | Get repayment schedule |
| GET | `/api/installments/with-history` | Get installments with history |
| POST | `/api/installments/{id}/pay` | Pay an installment |

### Required Headers

All requests require:
```
Authorization: Bearer {token}
Accept: application/json
```

POST requests also require:
```
Content-Type: application/json
```

---

**End of BNPL Repayment Routes Guide**

