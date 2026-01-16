# Frontend User Routes Guide - TrooSolar Backend
**Last Updated:** 2024-12-27  
**Purpose:** Complete reference for all user-facing API endpoints for frontend integration

---

## 📑 Table of Contents

1. [Authentication & User Management](#authentication--user-management)
2. [BNPL (Buy Now Pay Later) Routes](#bnpl-buy-now-pay-later-routes)
3. [BNPL Orders & Repayment Routes](#bnpl-orders--repayment-routes)
4. [Buy Now Flow Routes](#buy-now-flow-routes)
5. [Order Management Routes](#order-management-routes)
6. [Loan Management Routes](#loan-management-routes)
7. [Audit Request Routes](#audit-request-routes)
8. [Cart System Routes](#cart-system-routes)
9. [Wallet & Transactions Routes](#wallet--transactions-routes)
10. [Support & Communication Routes](#support--communication-routes)
11. [Configuration Routes](#configuration-routes)

---

## 🔐 Authentication & User Management

### Public Routes (No Auth Required)
```http
POST   /api/register
POST   /api/verify-otp/{user_id}
POST   /api/login
POST   /api/admin-login
POST   /api/forget-password
POST   /api/verify-reset-password-otp
POST   /api/reset-password
```

### Protected Routes (Auth Required)
```http
POST   /api/logout
POST   /api/update-user
GET    /api/send-otp
GET    /api/single-user/{user_id}
DELETE /api/delete-user/{user_id}
GET    /api/get-referral-details
```

---

## 💳 BNPL (Buy Now Pay Later) Routes

### Application Management
```http
GET    /api/bnpl/applications
       Query Params: status (optional), per_page (optional, default: 15)
       Returns: List of all user's BNPL applications with pagination

POST   /api/bnpl/apply
       Body: customer_type, product_category, loan_amount, repayment_duration, 
             credit_check_method, personal_details, property_details, 
             bank_statement (file), live_photo (file)
       Returns: Created loan application

GET    /api/bnpl/status/{application_id}
       Returns: Detailed application status with loan calculation and guarantor info

GET    /api/bnpl/applications/{application_id}/repayment-schedule
       Returns: Complete repayment schedule with installments, payment dates, 
                and summary statistics
```

### Guarantor Management
```http
POST   /api/bnpl/guarantor/invite
       Body: loan_application_id, full_name, email, phone, bvn, relationship
       Returns: Created guarantor record

POST   /api/bnpl/guarantor/upload
       Body: guarantor_id, signed_form (file)
       Returns: Updated guarantor with form path

POST   /api/bnpl/counteroffer/accept
       Body: application_id, minimum_deposit, minimum_tenor
       Returns: Updated application status
```

---

## 🛒 BNPL Orders & Repayment Routes

### BNPL Orders
```http
GET    /api/bnpl/orders
       Query Params: status (optional), per_page (optional, default: 15)
       Returns: List of all user's BNPL orders with loan summary

GET    /api/bnpl/orders/{order_id}
       Returns: Complete BNPL order details including:
                - Order information
                - Loan application details
                - Complete repayment schedule
                - Repayment summary (total, paid, pending, overdue)
                - Repayment history
                - Loan calculation details
```

### Loan Installments & Repayments
```http
GET    /api/installments/with-history
       Returns: Current month installments and history with overdue information
       Response includes:
       - current_month: Array of current month installments
       - history: Array of past installments
       - isActive: Boolean (has any paid installments)
       - isCompleted: Boolean (all installments paid)
       - hasOverdue: Boolean (has overdue installments)
       - overdueCount: Number of overdue installments
       - overdueAmount: Total overdue amount
       - loan: MonoLoanCalculation details

POST   /api/installments/{installmentId}/pay
       Body: {
         method: "wallet" | "bank" | "card" | "transfer" (required),
         type: "shop" | "loan" (required if method=wallet),
         tx_id: string (required if method != wallet),
         reference: string (optional),
         title: string (optional, default: "Loan installment payment")
       }
       Returns: Updated installment with transaction details
       
       Payment Methods:
       - wallet: Pay from user's wallet (shop_balance or loan_balance)
       - bank: Bank transfer (requires tx_id from payment gateway)
       - card: Card payment (requires tx_id from payment gateway)
       - transfer: Bank transfer (requires tx_id from payment gateway)

GET    /api/show-loan-installment/{monoCalculationId}
       Returns: All installments for a specific loan calculation
       Response: Array of installments with:
       - installment: Installment number (1, 2, 3...)
       - status: Payment status
       - amount: Installment amount
       - created_at: Creation timestamp
```

---

## 🛍️ Buy Now Flow Routes

### Checkout & Order Creation
```http
POST   /api/orders/checkout
       Body: product_id OR bundle_id OR amount, customer_type, product_category,
             installer_choice, include_insurance, include_inspection,
             state_id, delivery_location_id, add_ons[], audit_type, etc.
       Returns: Invoice breakdown with order_id

GET    /api/orders/{id}/summary
       Returns: Order summary with items, appliances, backup time

GET    /api/orders/{id}/invoice-details
       Returns: Detailed invoice breakdown (inverter, panels, batteries, fees)

POST   /api/order/payment-confirmation
       Body: amount, orderId, txId, type (direct|audit|wallet)
       Returns: Payment confirmation with transaction details

POST   /api/order/pay-by-loan
       Body: order_id, amount
       Returns: Payment confirmation using loan wallet
```

---

## 📦 Order Management Routes

### General Order Routes
```http
GET    /api/orders
       Returns: List of all user's orders (or all orders if admin)

GET    /api/orders/{id}
       Returns: Single order details with items and delivery address

POST   /api/orders
       Body: delivery_address_id, payment_method, note
       Returns: Created order from cart

DELETE /api/orders/{id}
       Returns: Deleted order confirmation

GET    /api/orders/user/{userId}
       Returns: Orders for specific user (admin usage)
```

---

## 💰 Loan Management Routes

### Loan Calculation
```http
POST   /api/loan-calculation
       Body: loan_amount, repayment_duration, deposit_percentage, etc.
       Returns: Created loan calculation with status 'calculated'

POST   /api/loan-calculation-finalized/{id}
       Returns: Finalized loan calculation

GET    /api/loan-calculation-stauts
       Returns: Current loan calculation status

GET    /api/offered-loan-calculation
       Returns: Admin-offered loan calculation (status: 'offered')

POST   /api/loan-calculator-tool
       Body: loan_amount, repayment_duration, deposit_percentage
       Returns: Calculated loan terms (no database save)
```

### Loan Application & Documents
```http
POST   /api/loan-application/{monoLoanCalculationId}
       Body: Documents and application details
       Returns: Created loan application

GET    /api/all-loan-application
       Returns: List of all user's loan applications

GET    /api/single-loan-application/{id}
       Returns: Single loan application details

DELETE /api/delete-loan-application/{loanApplicationId}
       Returns: Deleted application confirmation

POST   /api/beneficiary-detail/{monoLoanCalculationId}
       Body: Beneficiary information
       Returns: Updated loan application

POST   /api/loan-details/{monoLoanCalculationId}
       Body: Loan details
       Returns: Updated loan application

GET    /api/single-document/{mono_loan_calculation_id}
       Returns: Document details

GET    /api/single-beneficiary/{mono_loan_calculation_id}
       Returns: Beneficiary details

GET    /api/single-loan-detail/{mono_loan_calculation_id}
       Returns: Loan detail information
```

### Loan History & Status
```http
POST   /api/loan-history/{loanApplicatioId}
       Returns: Loan history with repayments and distribution info

GET    /api/all-loan-status
       Returns: All loan statuses for user

GET    /api/loan-dashboard
       Returns: Loan dashboard summary
```

---

## 🏠 Audit Request Routes

### Audit Request Management
```http
POST   /api/audit/request
       Body: audit_type (home-office|commercial), customer_type, 
             property_details (required for home-office, optional for commercial)
       Returns: Created audit request (and order for home-office)

GET    /api/audit/request/{id}
       Returns: Single audit request status

GET    /api/audit/requests
       Returns: List of all user's audit requests
```

---

## 🛍️ Cart System Routes

### Cart Management
```http
GET    /api/cart
       Returns: User's cart with all items

POST   /api/cart
       Body: itemable_type (product|bundles), itemable_id, quantity, unit_price
       Returns: Added cart item

PUT    /api/cart/{id}
       Body: quantity
       Returns: Updated cart item

DELETE /api/cart/{id}
       Returns: Removed cart item

DELETE /api/cart
       Returns: Cleared cart confirmation

GET    /api/cart/checkout-summary
       Returns: Cart checkout summary with totals

GET    /api/cart/access/{token}
       Returns: Access cart via email token (public route)
```

---

## 💰 Wallet & Transactions Routes

### Wallet Management
```http
GET    /api/loan-wallet
       Returns: User's loan wallet balance and details

POST   /api/fund-wallet
       Body: amount, method, tx_id
       Returns: Updated wallet with transaction
```

### Transactions
```http
GET    /api/transactions
       Returns: List of all user's transactions

GET    /api/transactions/{id}
       Returns: Single transaction details

GET    /api/transactions/user/{userId}
       Returns: Transactions for specific user

GET    /api/single-trancastion
       Returns: Single transaction (current user)

POST   /api/withdraw
       Body: amount, method, account_details
       Returns: Created withdrawal request

GET    /api/withdraw/get
       Returns: User's withdrawal requests
```

---

## 💬 Support & Communication Routes

### Tickets
```http
GET    /api/website/tickets
       Returns: List of user's tickets

POST   /api/website/tickets
       Body: subject, message, priority, category
       Returns: Created ticket

GET    /api/website/tickets/{id}
       Returns: Single ticket with replies

PUT    /api/website/tickets/{id}
       Body: subject, message, status
       Returns: Updated ticket

DELETE /api/website/tickets/{id}
       Returns: Deleted ticket confirmation
```

### Notifications
```http
GET    /api/user-notifications
       Returns: User's notifications
```

---

## ⚙️ Configuration Routes

### Public Configuration
```http
GET    /api/config/customer-types
       Returns: Available customer types (residential, sme, commercial)

GET    /api/config/audit-types
       Returns: Available audit types (home-office, commercial)

GET    /api/config/states
       Returns: List of states with delivery/installation fees

GET    /api/config/loan-configuration
       Returns: Loan configuration (tenor options, interest rates, etc.)

GET    /api/config/add-ons
       Returns: Available add-ons

GET    /api/config/delivery-locations
       Returns: Delivery locations with fees
```

---

## 📅 Calendar & Scheduling Routes

### Calendar Slots
```http
GET    /api/calendar/slots
       Query Params: type (installation|audit), date (optional)
       Returns: Available calendar slots for booking
```

---

## 🔍 KYC Routes

### KYC Management
```http
POST   /api/kyc
       Body: Documents and KYC information
       Returns: Created KYC submission

GET    /api/kyc/status
       Returns: User's KYC status

POST   /api/kyc/{kyc}/replace-file
       Body: file, document_type
       Returns: Updated KYC with replaced file
```

---

## 📊 Response Format

All API responses follow this structure:

### Success Response
```json
{
  "status": "success",
  "data": {
    // Response data
  },
  "message": "Human-readable success message"
}
```

### Error Response
```json
{
  "status": "error",
  "message": "Human-readable error message",
  "errors": {
    // Validation errors (if applicable)
  }
}
```

### Pagination Response
```json
{
  "status": "success",
  "data": {
    "data": [...],
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 15,
      "total": 75,
      "from": 1,
      "to": 15
    }
  },
  "message": "Data retrieved successfully"
}
```

---

## 🔑 Authentication

All protected routes require authentication via Laravel Sanctum.

### Headers Required
```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Getting Token
After login via `/api/login`, the response includes:
```json
{
  "status": "success",
  "data": {
    "user": {...},
    "token": "1|xxxxxxxxxxxxx"
  }
}
```

Use this token in the `Authorization` header for subsequent requests.

---

## 📝 Important Notes

### BNPL Order Flow
1. User creates loan calculation → `POST /api/loan-calculation`
2. Admin offers loan → User calls `GET /api/offered-loan-calculation`
3. User applies for BNPL → `POST /api/bnpl/apply`
4. Admin approves → User invites guarantor → `POST /api/bnpl/guarantor/invite`
5. Order created → User can view via `GET /api/bnpl/orders`
6. View repayment schedule → `GET /api/bnpl/orders/{order_id}` or `GET /api/bnpl/applications/{application_id}/repayment-schedule`
7. Pay installments → `POST /api/installments/{installmentId}/pay`

### File Uploads
- **Bank Statement:** PDF, JPG, PNG (max 10MB)
- **Live Photo:** JPG, PNG (max 5MB)
- **Guarantor Form:** PDF, JPG, PNG (max 10MB)
- **Profile Picture:** Image (max 2MB)

Use `multipart/form-data` content type for file uploads.

### Date Formats
- API expects: `YYYY-MM-DD` for dates
- API returns: `YYYY-MM-DD` or `YYYY-MM-DD HH:MM:SS` for timestamps

### Error Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized (missing/invalid token)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

---

## 🎯 Quick Reference: Most Used Routes

### For BNPL Orders & Repayments
```http
GET    /api/bnpl/orders                    # List all BNPL orders
GET    /api/bnpl/orders/{order_id}         # Get order with full repayment details
GET    /api/bnpl/applications/{id}/repayment-schedule  # Get repayment schedule
GET    /api/installments/with-history      # Get installments with history
POST   /api/installments/{id}/pay          # Pay an installment
```

### For Order Management
```http
GET    /api/orders                         # List all orders
GET    /api/orders/{id}                    # Get single order
POST   /api/orders/checkout                # Create order (Buy Now)
POST   /api/order/payment-confirmation     # Confirm payment
```

### For Loan Applications
```http
GET    /api/bnpl/applications              # List BNPL applications
GET    /api/bnpl/status/{id}               # Get application status
POST   /api/bnpl/apply                     # Submit BNPL application
```

---

## 💳 Repayment Payment Flow (Detailed Guide)

### Complete Payment Flow for BNPL Repayments

#### Step 1: Get Repayment Schedule
```http
GET /api/bnpl/orders/{order_id}
   OR
GET /api/bnpl/applications/{application_id}/repayment-schedule
```
Returns all installments with their IDs, amounts, due dates, and status.

#### Step 2: Select Installment to Pay
From the repayment schedule, identify the installment you want to pay:
- Get the `id` of the installment
- Check `status` (should be "pending" or "overdue")
- Note the `amount` to be paid
- Check `payment_date` for due date

#### Step 3: Pay Installment
```http
POST /api/installments/{installmentId}/pay
```

### Payment Method Details

#### 1. Wallet Payment (Shop Balance)
```json
{
  "method": "wallet",
  "type": "shop",
  "reference": "INSTALLMENT#123" // optional
}
```
- Deducts from user's `shop_balance`
- Requires sufficient shop balance
- Returns immediately with transaction details

#### 2. Wallet Payment (Loan Balance)
```json
{
  "method": "wallet",
  "type": "loan",
  "reference": "INSTALLMENT#123" // optional
}
```
- Deducts from user's `loan_balance`
- Requires sufficient loan balance
- Returns immediately with transaction details

#### 3. Bank Transfer / Card Payment
```json
{
  "method": "bank", // or "card" or "transfer"
  "tx_id": "TXN123456789", // Transaction ID from payment gateway
  "reference": "INSTALLMENT#123", // optional
  "title": "Loan Installment Payment" // optional
}
```
- Requires `tx_id` from your payment gateway (Flutterwave, Paystack, etc.)
- Payment gateway processes the payment first
- Then call this endpoint with the transaction ID
- Returns updated installment with transaction details

### Payment Response
```json
{
  "status": "success",
  "message": "Installment paid successfully",
  "data": {
    "id": 123,
    "mono_calculation_id": 45,
    "amount": 50000.00,
    "payment_date": "2024-01-15",
    "status": "paid",
    "computed_status": "paid",
    "paid_at": "2024-01-10 14:30:00",
    "remaining_duration": 5,
    "transaction": {
      "id": 789,
      "tx_id": "TXN123456789",
      "method": "bank",
      "type": "debit",
      "status": "success",
      "amount": 50000.00,
      "reference": "INSTALLMENT#123",
      "transacted_at": "2024-01-10 14:30:00"
    }
  }
}
```

### Error Responses

#### Insufficient Balance (Wallet)
```json
{
  "status": "error",
  "message": "Insufficient shop balance",
  "errors": {
    "shop_balance": ["Insufficient balance"]
  }
}
```

#### Already Paid
```json
{
  "status": "success",
  "message": "Installment already paid",
  "data": { /* installment details */ }
}
```

#### Validation Error
```json
{
  "status": "error",
  "message": "Validation failed.",
  "errors": {
    "method": ["Payment method is required."],
    "type": ["type is required and must be one of: shop, loan when method=wallet"],
    "tx_id": ["tx_id is required for non-wallet methods"]
  }
}
```

---

## 📱 Flutter Integration Examples

### Example 1: Get Repayment Schedule
```dart
Future<Map<String, dynamic>> getRepaymentSchedule(int orderId) async {
  final response = await http.get(
    Uri.parse('$baseUrl/api/bnpl/orders/$orderId'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    return data['data'];
  } else {
    throw Exception('Failed to load repayment schedule');
  }
}
```

### Example 2: Pay Installment with Wallet
```dart
Future<Map<String, dynamic>> payInstallmentWithWallet({
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

### Example 3: Pay Installment with Flutterwave
```dart
Future<Map<String, dynamic>> payInstallmentWithFlutterwave({
  required int installmentId,
  required double amount,
  required String email,
  required String phone,
}) async {
  // Step 1: Initialize Flutterwave payment
  final flutterwaveResponse = await initializeFlutterwavePayment(
    amount: amount,
    email: email,
    phone: phone,
    txRef: 'INSTALLMENT#$installmentId-${DateTime.now().millisecondsSinceEpoch}',
  );
  
  // Step 2: After successful payment, get transaction ID
  final txId = flutterwaveResponse['data']['tx_ref'];
  
  // Step 3: Confirm payment with backend
  final response = await http.post(
    Uri.parse('$baseUrl/api/installments/$installmentId/pay'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: json.encode({
      'method': 'card', // or 'bank' or 'transfer'
      'tx_id': txId,
      'reference': 'INSTALLMENT#$installmentId',
      'title': 'Loan Installment Payment',
    }),
  );
  
  if (response.statusCode == 200) {
    return json.decode(response.body);
  } else {
    final error = json.decode(response.body);
    throw Exception(error['message'] ?? 'Payment confirmation failed');
  }
}

// Helper function to initialize Flutterwave payment
Future<Map<String, dynamic>> initializeFlutterwavePayment({
  required double amount,
  required String email,
  required String phone,
  required String txRef,
}) async {
  // Use Flutterwave SDK to initialize payment
  // This is a placeholder - use actual Flutterwave SDK
  return await FlutterwaveSDK.initializePayment(
    amount: amount,
    email: email,
    phone: phone,
    txRef: txRef,
  );
}
```

### Example 4: Get Installments with History
```dart
Future<Map<String, dynamic>> getInstallmentsWithHistory() async {
  final response = await http.get(
    Uri.parse('$baseUrl/api/installments/with-history'),
    headers: {
      'Authorization': 'Bearer $token',
      'Accept': 'application/json',
    },
  );
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    return data['data'];
  } else {
    throw Exception('Failed to load installments');
  }
}

// Usage example
void displayInstallments() async {
  try {
    final data = await getInstallmentsWithHistory();
    
    final currentMonth = data['current_month'] as List;
    final history = data['history'] as List;
    final hasOverdue = data['hasOverdue'] as bool;
    final overdueAmount = data['overdueAmount'] as double;
    
    // Display current month installments
    for (var installment in currentMonth) {
      print('Amount: ${installment['amount']}');
      print('Due Date: ${installment['payment_date']}');
      print('Status: ${installment['status']}');
      print('Overdue: ${installment['is_overdue']}');
    }
    
    // Show overdue warning
    if (hasOverdue) {
      print('You have overdue payments: ₦$overdueAmount');
    }
  } catch (e) {
    print('Error: $e');
  }
}
```

### Example 5: Complete Payment Flow Widget
```dart
class InstallmentPaymentWidget extends StatefulWidget {
  final int installmentId;
  final double amount;
  final String dueDate;
  
  const InstallmentPaymentWidget({
    Key? key,
    required this.installmentId,
    required this.amount,
    required this.dueDate,
  }) : super(key: key);
  
  @override
  _InstallmentPaymentWidgetState createState() => _InstallmentPaymentWidgetState();
}

class _InstallmentPaymentWidgetState extends State<InstallmentPaymentWidget> {
  String _selectedMethod = 'wallet';
  String _walletType = 'shop';
  bool _isLoading = false;
  
  Future<void> _payInstallment() async {
    setState(() => _isLoading = true);
    
    try {
      Map<String, dynamic>? response;
      
      if (_selectedMethod == 'wallet') {
        // Check wallet balance first
        final wallet = await getWalletBalance();
        final availableBalance = _walletType == 'shop' 
            ? wallet['shop_balance'] 
            : wallet['loan_balance'];
            
        if (availableBalance < widget.amount) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Insufficient ${_walletType} balance')),
          );
          return;
        }
        
        response = await payInstallmentWithWallet(
          installmentId: widget.installmentId,
          walletType: _walletType,
        );
      } else {
        // Initialize payment gateway
        final txId = await initializePaymentGateway(
          amount: widget.amount,
          method: _selectedMethod,
        );
        
        response = await payInstallmentWithGateway(
          installmentId: widget.installmentId,
          txId: txId,
          method: _selectedMethod,
        );
      }
      
      if (response != null && response['status'] == 'success') {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Payment successful!')),
        );
        Navigator.pop(context, true); // Return success
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Payment failed: ${e.toString()}')),
      );
    } finally {
      setState(() => _isLoading = false);
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Pay Installment')),
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Card(
              child: Padding(
                padding: EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Amount: ₦${widget.amount.toStringAsFixed(2)}'),
                    SizedBox(height: 8),
                    Text('Due Date: ${widget.dueDate}'),
                  ],
                ),
              ),
            ),
            SizedBox(height: 24),
            Text('Payment Method:', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            SizedBox(height: 8),
            DropdownButton<String>(
              value: _selectedMethod,
              items: ['wallet', 'bank', 'card', 'transfer'].map((method) {
                return DropdownMenuItem(
                  value: method,
                  child: Text(method.toUpperCase()),
                );
              }).toList(),
              onChanged: (value) => setState(() => _selectedMethod = value!),
            ),
            if (_selectedMethod == 'wallet') ...[
              SizedBox(height: 16),
              Text('Wallet Type:', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              SizedBox(height: 8),
              DropdownButton<String>(
                value: _walletType,
                items: ['shop', 'loan'].map((type) {
                  return DropdownMenuItem(
                    value: type,
                    child: Text(type.toUpperCase()),
                  );
                }).toList(),
                onChanged: (value) => setState(() => _walletType = value!),
              ),
            ],
            SizedBox(height: 24),
            ElevatedButton(
              onPressed: _isLoading ? null : _payInstallment,
              child: _isLoading 
                  ? CircularProgressIndicator() 
                  : Text('Pay Installment'),
            ),
          ],
        ),
      ),
    );
  }
}
```

---

## 🔄 Complete Repayment Payment Workflow

### User Journey:
1. **View BNPL Orders** → `GET /api/bnpl/orders`
2. **Select Order** → `GET /api/bnpl/orders/{order_id}` (get repayment schedule)
3. **View Installments** → See all installments with status, amounts, due dates
4. **Select Installment to Pay** → Choose pending/overdue installment
5. **Choose Payment Method** → Wallet (shop/loan) or Gateway (bank/card/transfer)
6. **Process Payment**:
   - **Wallet**: Direct deduction → `POST /api/installments/{id}/pay`
   - **Gateway**: Initialize payment → Get tx_id → `POST /api/installments/{id}/pay`
7. **Confirm Payment** → Check response for transaction details
8. **Refresh Schedule** → `GET /api/bnpl/orders/{order_id}` to see updated status

### Best Practices:
- Always check installment status before attempting payment
- Verify wallet balance before wallet payments
- Store transaction IDs for reference
- Handle payment failures gracefully
- Show loading states during payment processing
- Refresh repayment schedule after successful payment

---

**End of Frontend User Routes Guide**

