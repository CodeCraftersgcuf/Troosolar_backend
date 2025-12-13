# Admin Analytics API Documentation

**Last Updated:** 2024-12-27  
**Purpose:** Complete API documentation for Admin Analytics Dashboard

---

## 📋 Overview

The Analytics API provides comprehensive statistics and metrics for the admin dashboard, including general metrics, financial analytics, and revenue analytics. All data can be filtered by time period.

---

## 🔐 Authentication

**Required:** Admin authentication via Sanctum
```
Headers:
  Authorization: Bearer {admin_token}
  Accept: application/json
```

---

## 📍 API Endpoint

### Get Analytics Data
**GET** `/api/admin/analytics`

#### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `period` | string | No | `all_time` | Time period: `all_time`, `daily`, `weekly`, `monthly`, `yearly` |

#### Request Examples
```
GET /api/admin/analytics
GET /api/admin/analytics?period=daily
GET /api/admin/analytics?period=weekly
GET /api/admin/analytics?period=monthly
GET /api/admin/analytics?period=yearly
GET /api/admin/analytics?period=all_time
```

---

## 📊 Response Structure

### Response Format
```json
{
  "status": "success",
  "message": "Analytics data fetched successfully",
  "data": {
    "period": "monthly",
    "date_range": {
      "start": "2024-12-01",
      "end": "2024-12-31"
    },
    "general": { ... },
    "financial": { ... },
    "revenue": { ... }
  }
}
```

---

## 📈 General Analytics

### Fields
| Field | Type | Description |
|-------|------|-------------|
| `total_users` | integer | Total number of registered users (all time) |
| `active_users` | integer | Users with activity in last 30 days |
| `total_orders` | integer | Total orders (all time) |
| `orders_in_period` | integer | Orders in selected period |
| `bounce_rate` | float | Percentage of users who registered but never placed order |
| `deleted_accounts` | integer | Number of deleted accounts |
| `total_revenue` | string | Total revenue from confirmed orders (all time) |
| `revenue_in_period` | string | Revenue in selected period |
| `total_deposits` | string | Total deposits (all time) |
| `deposits_in_period` | string | Deposits in selected period |
| `total_withdrawals` | string | Total approved withdrawals (all time) |
| `withdrawals_in_period` | string | Withdrawals in selected period |
| `admin_earnings` | string | Admin earnings (calculated from revenue) |
| `top_selling_product` | string | Name of top selling product |

### Example Response
```json
{
  "general": {
    "total_users": 20000,
    "active_users": 300,
    "total_orders": 300,
    "orders_in_period": 45,
    "bounce_rate": 65.5,
    "deleted_accounts": 20,
    "total_revenue": "5,000,000.00",
    "revenue_in_period": "750,000.00",
    "total_deposits": "3,500,000.00",
    "deposits_in_period": "500,000.00",
    "total_withdrawals": "500,000.00",
    "withdrawals_in_period": "75,000.00",
    "admin_earnings": "5,000,000.00",
    "top_selling_product": "5KVA Inverter"
  }
}
```

---

## 💰 Financial Analytics

### Fields
| Field | Type | Description |
|-------|------|-------------|
| `total_loans` | integer | Total loan applications (all time) |
| `loans_in_period` | integer | Loan applications in selected period |
| `approved_loans` | integer | Approved loan applications (all time) |
| `approved_loans_in_period` | integer | Approved loans in selected period |
| `rejected_loans` | integer | Rejected loan applications (all time) |
| `rejected_loans_in_period` | integer | Rejected loans in selected period |
| `pending_loans` | integer | Pending loan applications (all time) |
| `pending_loans_in_period` | integer | Pending loans in selected period |
| `loan_amount_disbursed` | string | Total loan amount disbursed (all time) |
| `amount_disbursed_in_period` | string | Amount disbursed in selected period |
| `top_partner` | string | Partner with most loans sent |
| `overdue_loans` | integer | Number of loans with overdue installments |
| `overdue_loan_amount` | string | Total overdue amount |
| `loan_default_rate` | float | Percentage of approved loans that are overdue |
| `repayment_completion_rate` | float | Percentage of installments that are paid |

### Example Response
```json
{
  "financial": {
    "total_loans": 100,
    "loans_in_period": 15,
    "approved_loans": 60,
    "approved_loans_in_period": 10,
    "rejected_loans": 25,
    "rejected_loans_in_period": 3,
    "pending_loans": 15,
    "pending_loans_in_period": 2,
    "loan_amount_disbursed": "20,000,000.00",
    "amount_disbursed_in_period": "3,000,000.00",
    "top_partner": "ABC Partner",
    "overdue_loans": 12,
    "overdue_loan_amount": "200,000.00",
    "loan_default_rate": 20.0,
    "repayment_completion_rate": 75.5
  }
}
```

---

## 💵 Revenue Analytics

### Fields
| Field | Type | Description |
|-------|------|-------------|
| `total_revenue` | string | Total revenue (all time) |
| `revenue_in_period` | string | Revenue in selected period |
| `revenue_by_product` | array | Top 10 products by revenue in period |
| `delivery_fees` | string | Total delivery fees in period |
| `installation_fees` | string | Total installation fees in period |
| `revenue_growth_rate` | float | Percentage growth from previous period |
| `interests_earned` | string | Total interest earned from loans |

### Revenue by Product Structure
```json
{
  "revenue_by_product": [
    {
      "product_id": 45,
      "product_name": "5KVA Inverter",
      "revenue": "500,000.00"
    },
    {
      "product_id": 46,
      "product_name": "3KVA Inverter",
      "revenue": "300,000.00"
    }
  ]
}
```

### Example Response
```json
{
  "revenue": {
    "total_revenue": "10,000,000.00",
    "revenue_in_period": "1,500,000.00",
    "revenue_by_product": [
      {
        "product_id": 45,
        "product_name": "5KVA Inverter",
        "revenue": "500,000.00"
      }
    ],
    "delivery_fees": "150,000.00",
    "installation_fees": "300,000.00",
    "revenue_growth_rate": 25.5,
    "interests_earned": "1,200,000.00"
  }
}
```

---

## ⏰ Time Periods

### All Time
- **Start Date:** 2020-01-01 (or oldest record)
- **End Date:** Current date/time
- **Comparison:** None

### Daily
- **Start Date:** Start of today
- **End Date:** End of today
- **Previous Period:** Yesterday

### Weekly
- **Start Date:** Start of current week (Monday)
- **End Date:** End of current week (Sunday)
- **Previous Period:** Previous week

### Monthly
- **Start Date:** Start of current month
- **End Date:** End of current month
- **Previous Period:** Previous month

### Yearly
- **Start Date:** Start of current year
- **End Date:** End of current year
- **Previous Period:** Previous year

---

## 📊 Calculation Methods

### Bounce Rate
```
Bounce Rate = ((Total Users - Users with Orders) / Total Users) × 100
```
Percentage of users who registered but never placed an order.

### Loan Default Rate
```
Loan Default Rate = (Overdue Loans / Approved Loans) × 100
```
Percentage of approved loans that have overdue installments.

### Repayment Completion Rate
```
Repayment Completion Rate = (Paid Installments / Total Installments) × 100
```
Percentage of loan installments that have been paid.

### Revenue Growth Rate
```
Revenue Growth Rate = ((Current Period Revenue - Previous Period Revenue) / Previous Period Revenue) × 100
```
Percentage change in revenue from previous period to current period.

### Active Users
Users who have:
- Registered in last 30 days, OR
- Have activity logs in last 30 days

### Interest Earned
Calculated from `MonoLoanCalculation`:
```
Interest = total_amount - loan_amount
```
Sum of interest from all loans that have repayments.

---

## 🔍 Data Sources

### General Metrics
- **Users:** `users` table
- **Orders:** `orders` table
- **Transactions:** `transactions` table
- **Withdrawals:** `withdraw_requests` table
- **Top Product:** `order_items` joined with `products`

### Financial Metrics
- **Loans:** `loan_applications` table
- **Loan Status:** `loan_applications.status` field
- **Disbursements:** `loan_distributes` table
- **Partners:** `partners` table
- **Overdue:** `loan_installments` table
- **Repayments:** `loan_repayments` table

### Revenue Metrics
- **Revenue:** `orders` table (payment_status = 'confirmed')
- **Revenue by Product:** `order_items` joined with `products`
- **Delivery Fees:** `orders.delivery_fee`
- **Installation Fees:** `orders.installation_price`
- **Interest:** `mono_loan_calculations` table

---

## ⚠️ Error Handling

### Error Response (500)
```json
{
  "status": "error",
  "message": "Failed to fetch analytics data: [error details]"
}
```

### Common Issues
1. **Database Connection:** Ensure database is accessible
2. **Missing Tables:** Ensure all migrations have been run
3. **Performance:** Large datasets may take longer to calculate

---

## 💡 Usage Examples

### Frontend Integration

#### React/Vue Example
```javascript
// Fetch analytics for current month
async function fetchAnalytics(period = 'monthly') {
  try {
    const response = await fetch(`/api/admin/analytics?period=${period}`, {
      headers: {
        'Authorization': `Bearer ${adminToken}`,
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      const { general, financial, revenue } = data.data;
      
      // Display general metrics
      console.log('Total Users:', general.total_users);
      console.log('Active Users:', general.active_users);
      console.log('Total Revenue:', general.total_revenue);
      
      // Display financial metrics
      console.log('Total Loans:', financial.total_loans);
      console.log('Approved Loans:', financial.approved_loans);
      console.log('Overdue Loans:', financial.overdue_loans);
      
      // Display revenue metrics
      console.log('Revenue Growth:', revenue.revenue_growth_rate + '%');
      console.log('Top Products:', revenue.revenue_by_product);
    }
  } catch (error) {
    console.error('Error fetching analytics:', error);
  }
}

// Fetch for different periods
fetchAnalytics('daily');
fetchAnalytics('weekly');
fetchAnalytics('monthly');
fetchAnalytics('yearly');
fetchAnalytics('all_time');
```

---

## 📋 Complete Response Example

```json
{
  "status": "success",
  "message": "Analytics data fetched successfully",
  "data": {
    "period": "monthly",
    "date_range": {
      "start": "2024-12-01",
      "end": "2024-12-31"
    },
    "general": {
      "total_users": 20000,
      "active_users": 300,
      "total_orders": 300,
      "orders_in_period": 45,
      "bounce_rate": 65.5,
      "deleted_accounts": 20,
      "total_revenue": "5,000,000.00",
      "revenue_in_period": "750,000.00",
      "total_deposits": "3,500,000.00",
      "deposits_in_period": "500,000.00",
      "total_withdrawals": "500,000.00",
      "withdrawals_in_period": "75,000.00",
      "admin_earnings": "5,000,000.00",
      "top_selling_product": "5KVA Inverter"
    },
    "financial": {
      "total_loans": 100,
      "loans_in_period": 15,
      "approved_loans": 60,
      "approved_loans_in_period": 10,
      "rejected_loans": 25,
      "rejected_loans_in_period": 3,
      "pending_loans": 15,
      "pending_loans_in_period": 2,
      "loan_amount_disbursed": "20,000,000.00",
      "amount_disbursed_in_period": "3,000,000.00",
      "top_partner": "ABC Partner",
      "overdue_loans": 12,
      "overdue_loan_amount": "200,000.00",
      "loan_default_rate": 20.0,
      "repayment_completion_rate": 75.5
    },
    "revenue": {
      "total_revenue": "10,000,000.00",
      "revenue_in_period": "1,500,000.00",
      "revenue_by_product": [
        {
          "product_id": 45,
          "product_name": "5KVA Inverter",
          "revenue": "500,000.00"
        },
        {
          "product_id": 46,
          "product_name": "3KVA Inverter",
          "revenue": "300,000.00"
        }
      ],
      "delivery_fees": "150,000.00",
      "installation_fees": "300,000.00",
      "revenue_growth_rate": 25.5,
      "interests_earned": "1,200,000.00"
    }
  }
}
```

---

## 🎯 Quick Reference

### Period Values
- `all_time` - All data from beginning
- `daily` - Today's data
- `weekly` - Current week
- `monthly` - Current month
- `yearly` - Current year

### Key Metrics
- **Total Users:** All registered users
- **Active Users:** Users active in last 30 days
- **Bounce Rate:** Users who never ordered
- **Revenue Growth:** Percentage change from previous period
- **Loan Default Rate:** Overdue loans percentage
- **Repayment Completion:** Paid installments percentage

---

## ✅ Testing Checklist

- [ ] Fetch analytics for all_time period
- [ ] Fetch analytics for daily period
- [ ] Fetch analytics for weekly period
- [ ] Fetch analytics for monthly period
- [ ] Fetch analytics for yearly period
- [ ] Verify all metrics are calculated correctly
- [ ] Verify date ranges are correct
- [ ] Test with no data
- [ ] Test with large datasets

---

**End of Documentation**

