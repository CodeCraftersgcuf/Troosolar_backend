# Repayment Payment Guide - Complete Reference
**Last Updated:** 2024-12-27  
**Purpose:** Comprehensive guide for implementing repayment payments in Flutter/mobile apps

---

## 📑 Table of Contents

1. [Overview](#overview)
2. [Payment Routes](#payment-routes)
3. [Payment Methods](#payment-methods)
4. [Payment Flow](#payment-flow)
5. [Flutter Integration](#flutter-integration)
6. [Error Handling](#error-handling)
7. [Best Practices](#best-practices)

---

## 🎯 Overview

Users can pay for their BNPL loan installments through multiple payment methods:
- **Wallet Payment** (Shop Balance or Loan Balance)
- **Bank Transfer** (via payment gateway)
- **Card Payment** (via payment gateway)
- **Direct Transfer** (via payment gateway)

All payments are processed through a single endpoint: `POST /api/installments/{installmentId}/pay`

---

## 🛣️ Payment Routes

### Primary Payment Route
```http
POST   /api/installments/{installmentId}/pay
```

### Supporting Routes
```http
GET    /api/installments/with-history
       - Get all installments (current month + history)
       - Shows overdue information
       - Includes loan details

GET    /api/bnpl/orders/{order_id}
       - Get BNPL order with complete repayment schedule
       - Includes all installments with IDs

GET    /api/bnpl/applications/{application_id}/repayment-schedule
       - Get repayment schedule for specific application
       - Includes installment IDs for payment
```

---

## 💳 Payment Methods

### 1. Wallet Payment (Shop Balance)

**Request:**
```json
{
  "method": "wallet",
  "type": "shop",
  "reference": "INSTALLMENT#123" // optional
}
```

**Process:**
1. Check user's `shop_balance` via `GET /api/loan-wallet`
2. Verify balance is sufficient
3. Call payment endpoint
4. Amount deducted from `shop_balance`
5. Transaction created automatically

**Response:**
```json
{
  "status": "success",
  "message": "Installment paid successfully",
  "data": {
    "id": 123,
    "amount": 50000.00,
    "status": "paid",
    "transaction": {
      "tx_id": "WALLET-SHOP-1234567890-123",
      "method": "wallet",
      "amount": 50000.00
    }
  }
}
```

### 2. Wallet Payment (Loan Balance)

**Request:**
```json
{
  "method": "wallet",
  "type": "loan",
  "reference": "INSTALLMENT#123" // optional
}
```

**Process:**
1. Check user's `loan_balance` via `GET /api/loan-wallet`
2. Verify balance is sufficient
3. Call payment endpoint
4. Amount deducted from `loan_balance`
5. Transaction created automatically

### 3. Bank Transfer / Card Payment

**Request:**
```json
{
  "method": "bank", // or "card" or "transfer"
  "tx_id": "TXN123456789", // Required: from payment gateway
  "reference": "INSTALLMENT#123", // optional
  "title": "Loan Installment Payment" // optional
}
```

**Process:**
1. Initialize payment with gateway (Flutterwave, Paystack, etc.)
2. User completes payment on gateway
3. Gateway returns transaction ID (`tx_id`)
4. Call payment endpoint with `tx_id`
5. Backend verifies and records payment
6. Installment status updated to "paid"

**Important:** The `tx_id` must come from your payment gateway after successful payment.

---

## 🔄 Payment Flow

### Complete Flow Diagram

```
┌─────────────────────────────────────────────────────────┐
│ 1. User Views Repayment Schedule                        │
│    GET /api/bnpl/orders/{order_id}                      │
└─────────────────┬───────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────┐
│ 2. User Selects Installment to Pay                      │
│    - Get installment ID                                 │
│    - Check amount and due date                          │
│    - Verify status is "pending" or "overdue"            │
└─────────────────┬───────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────────┐
│ 3. User Chooses Payment Method                           │
│    - Wallet (shop/loan)                                 │
│    - Bank/Card/Transfer (via gateway)                  │
└─────────────────┬───────────────────────────────────────┘
                  │
        ┌─────────┴─────────┐
        │                   │
        ▼                   ▼
┌───────────────┐   ┌──────────────────────┐
│ Wallet Path  │   │ Gateway Path         │
└───────┬───────┘   └──────────┬───────────┘
        │                      │
        ▼                      ▼
┌──────────────────┐  ┌──────────────────────────┐
│ Check Balance    │  │ Initialize Gateway      │
│ GET /api/loan-   │  │ - Flutterwave           │
│ wallet           │  │ - Paystack              │
└───────┬──────────┘  │ - etc.                  │
        │             └──────────┬───────────────┘
        │                        │
        ▼                        ▼
┌──────────────────┐  ┌──────────────────────────┐
│ Sufficient?      │  │ User Pays on Gateway    │
│ - Yes: Continue  │  │ - Card details          │
│ - No: Show Error│  │ - Bank transfer         │
└───────┬──────────┘  └──────────┬───────────────┘
        │                        │
        │                        ▼
        │             ┌──────────────────────────┐
        │             │ Gateway Returns tx_id     │
        │             └──────────┬───────────────┘
        │                        │
        └────────────┬───────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │ POST /api/installments/    │
        │ {id}/pay                   │
        │ - method                   │
        │ - type (if wallet)         │
        │ - tx_id (if gateway)       │
        └────────────┬───────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │ Payment Processed          │
        │ - Installment → "paid"     │
        │ - Transaction created      │
        │ - Wallet updated (if wallet)│
        └────────────┬───────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │ Refresh Repayment Schedule │
        │ GET /api/bnpl/orders/{id}  │
        └────────────────────────────┘
```

---

## 📱 Flutter Integration

### Step 1: Get Wallet Balance
```dart
Future<Map<String, dynamic>> getWalletBalance() async {
  final response = await http.get(
    Uri.parse('$baseUrl/api/loan-wallet'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    return data['data'];
  }
  throw Exception('Failed to load wallet');
}
```

### Step 2: Pay with Wallet
```dart
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
```

### Step 3: Pay with Flutterwave
```dart
import 'package:flutterwave_standard/flutterwave.dart';

Future<Map<String, dynamic>> payWithFlutterwave({
  required int installmentId,
  required double amount,
  required String email,
  required String phone,
  required String name,
}) async {
  // Step 1: Initialize Flutterwave
  final Customer customer = Customer(
    name: name,
    phoneNumber: phone,
    email: email,
  );
  
  final Flutterwave flutterwave = Flutterwave(
    context: context,
    publicKey: "YOUR_PUBLIC_KEY",
    currency: "NGN",
    paymentOptions: "card, banktransfer, ussd",
    amount: amount.toString(),
    customer: customer,
    txRef: 'INSTALLMENT#$installmentId-${DateTime.now().millisecondsSinceEpoch}',
    customization: Customization(
      title: "Loan Installment Payment",
      description: "Payment for installment #$installmentId",
    ),
    isTestMode: true, // Set to false in production
  );
  
  // Step 2: Process payment
  final ChargeResponse response = await flutterwave.charge();
  
  if (response.status == 'successful') {
    // Step 3: Confirm payment with backend
    final confirmResponse = await http.post(
      Uri.parse('$baseUrl/api/installments/$installmentId/pay'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: json.encode({
        'method': 'card', // or 'bank' based on payment option used
        'tx_id': response.transactionId ?? response.txRef,
        'reference': 'INSTALLMENT#$installmentId',
        'title': 'Loan Installment Payment',
      }),
    );
    
    if (confirmResponse.statusCode == 200) {
      return json.decode(confirmResponse.body);
    } else {
      throw Exception('Payment confirmation failed');
    }
  } else {
    throw Exception('Payment failed: ${response.message}');
  }
}
```

### Step 4: Complete Payment Widget
```dart
class PaymentScreen extends StatefulWidget {
  final Map<String, dynamic> installment;
  
  const PaymentScreen({Key? key, required this.installment}) : super(key: key);
  
  @override
  _PaymentScreenState createState() => _PaymentScreenState();
}

class _PaymentScreenState extends State<PaymentScreen> {
  String _selectedMethod = 'wallet';
  String _walletType = 'shop';
  bool _isLoading = false;
  Map<String, dynamic>? _walletBalance;
  
  @override
  void initState() {
    super.initState();
    _loadWalletBalance();
  }
  
  Future<void> _loadWalletBalance() async {
    try {
      final balance = await getWalletBalance();
      setState(() => _walletBalance = balance);
    } catch (e) {
      print('Error loading wallet: $e');
    }
  }
  
  Future<void> _processPayment() async {
    setState(() => _isLoading = true);
    
    try {
      Map<String, dynamic>? response;
      
      if (_selectedMethod == 'wallet') {
        // Check balance
        final balance = _walletType == 'shop' 
            ? _walletBalance?['shop_balance'] ?? 0.0
            : _walletBalance?['loan_balance'] ?? 0.0;
            
        if (balance < (widget.installment['amount'] as num).toDouble()) {
          _showError('Insufficient ${_walletType} balance');
          return;
        }
        
        response = await payWithWallet(
          installmentId: widget.installment['id'],
          walletType: _walletType,
        );
      } else {
        // Get user details for gateway
        final user = await getCurrentUser();
        
        response = await payWithFlutterwave(
          installmentId: widget.installment['id'],
          amount: (widget.installment['amount'] as num).toDouble(),
          email: user['email'],
          phone: user['phone'],
          name: '${user['first_name']} ${user['sur_name']}',
        );
      }
      
      if (response != null && response['status'] == 'success') {
        _showSuccess('Payment successful!');
        Navigator.pop(context, true);
      }
    } catch (e) {
      _showError('Payment failed: ${e.toString()}');
    } finally {
      setState(() => _isLoading = false);
    }
  }
  
  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.red),
    );
  }
  
  void _showSuccess(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.green),
    );
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Pay Installment')),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Installment Details Card
            Card(
              child: Padding(
                padding: EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Installment Details',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    SizedBox(height: 12),
                    _buildDetailRow('Amount', '₦${(widget.installment['amount'] as num).toStringAsFixed(2)}'),
                    _buildDetailRow('Due Date', widget.installment['payment_date'] ?? 'N/A'),
                    _buildDetailRow('Status', widget.installment['status'] ?? 'pending'),
                    if (widget.installment['is_overdue'] == true)
                      Container(
                        margin: EdgeInsets.only(top: 8),
                        padding: EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: Colors.red.shade50,
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          '⚠️ This installment is overdue',
                          style: TextStyle(color: Colors.red),
                        ),
                      ),
                  ],
                ),
              ),
            ),
            
            SizedBox(height: 24),
            
            // Payment Method Selection
            Text(
              'Payment Method',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 8),
            DropdownButtonFormField<String>(
              value: _selectedMethod,
              decoration: InputDecoration(
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              ),
              items: ['wallet', 'bank', 'card', 'transfer'].map((method) {
                return DropdownMenuItem(
                  value: method,
                  child: Text(method.toUpperCase()),
                );
              }).toList(),
              onChanged: (value) => setState(() => _selectedMethod = value!),
            ),
            
            // Wallet Type Selection (if wallet selected)
            if (_selectedMethod == 'wallet') ...[
              SizedBox(height: 16),
              Text(
                'Wallet Type',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
              SizedBox(height: 8),
              DropdownButtonFormField<String>(
                value: _walletType,
                decoration: InputDecoration(
                  border: OutlineInputBorder(),
                  contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                ),
                items: [
                  DropdownMenuItem(
                    value: 'shop',
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Shop Balance'),
                        if (_walletBalance != null)
                          Text(
                            'Available: ₦${(_walletBalance!['shop_balance'] ?? 0.0).toStringAsFixed(2)}',
                            style: TextStyle(fontSize: 12, color: Colors.grey),
                          ),
                      ],
                    ),
                  ),
                  DropdownMenuItem(
                    value: 'loan',
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Loan Balance'),
                        if (_walletBalance != null)
                          Text(
                            'Available: ₦${(_walletBalance!['loan_balance'] ?? 0.0).toStringAsFixed(2)}',
                            style: TextStyle(fontSize: 12, color: Colors.grey),
                          ),
                      ],
                    ),
                  ),
                ],
                onChanged: (value) => setState(() => _walletType = value!),
              ),
            ],
            
            SizedBox(height: 32),
            
            // Pay Button
            ElevatedButton(
              onPressed: _isLoading ? null : _processPayment,
              style: ElevatedButton.styleFrom(
                padding: EdgeInsets.symmetric(vertical: 16),
                backgroundColor: Colors.blue,
              ),
              child: _isLoading
                  ? SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : Text(
                      'Pay ₦${(widget.installment['amount'] as num).toStringAsFixed(2)}',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
            ),
          ],
        ),
      ),
    );
  }
  
  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(color: Colors.grey)),
          Text(value, style: TextStyle(fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }
}
```

---

## ⚠️ Error Handling

### Common Errors and Solutions

#### 1. Insufficient Balance
```json
{
  "status": "error",
  "message": "Insufficient shop balance",
  "errors": {
    "shop_balance": ["Insufficient balance"]
  }
}
```
**Solution:** Check balance before payment, show error to user.

#### 2. Already Paid
```json
{
  "status": "success",
  "message": "Installment already paid",
  "data": { /* installment details */ }
}
```
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
**Solution:** Ensure payment gateway returns tx_id before calling endpoint.

#### 4. Invalid Installment
```json
{
  "status": "error",
  "message": "No query results for model [App\\Models\\LoanInstallment]"
}
```
**Solution:** Verify installment ID exists and belongs to user.

---

## ✅ Best Practices

1. **Always Check Balance First**
   - For wallet payments, verify balance before showing payment option
   - Show available balance to user

2. **Verify Installment Status**
   - Don't allow payment for already paid installments
   - Show appropriate message if already paid

3. **Handle Payment Gateway Errors**
   - Catch and display gateway-specific errors
   - Provide retry option

4. **Store Transaction References**
   - Save tx_id for reference
   - Link to transaction history

5. **Refresh After Payment**
   - Reload repayment schedule after successful payment
   - Update UI to reflect new status

6. **Show Loading States**
   - Display loading indicator during payment
   - Disable buttons to prevent double payment

7. **Error Recovery**
   - Provide clear error messages
   - Allow user to retry payment
   - Log errors for debugging

8. **Security**
   - Never expose sensitive payment data
   - Use HTTPS for all requests
   - Validate all inputs

---

## 📝 Payment Status Flow

```
pending → (payment initiated) → processing → paid
   │
   └──→ (past due date) → overdue → (payment) → paid
```

### Status Meanings:
- **pending**: Not yet paid, not yet due
- **overdue**: Not paid, past due date
- **paid**: Successfully paid

---

**End of Repayment Payment Guide**

