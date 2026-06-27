# Mono Direct Debit — Setup Guide (TrooSolar BNPL)

This guide explains how to enable **Mono Direct Debit** for BNPL loan repayments and **Mono DirectPay** for the one-time credit check fee.

---

## Products (do not mix them up)

| Use case | Mono product | TrooSolar API |
|----------|--------------|---------------|
| Credit check fee (once, before verification) | **DirectPay** — one-time debit from linked bank | `POST /api/bnpl/credit-check-fee/mono/initiate` |
| Monthly loan installments | **Direct Debit** — recurring mandate | `POST /api/bnpl/mandate/initiate` |

---

## 1. Mono Dashboard setup

1. Log in to [Mono Dashboard](https://app.withmono.com).
2. Enable products for your app:
   - **Connect** (bank linking) — already used for BNPL credit check
   - **DirectPay** — for credit check fee
   - **Direct Debit** — for loan repayments
3. Copy keys into server `.env`:
   ```env
   MONO_PUBLIC_KEY=live_pk_...
   MONO_SECRET_KEY=live_sk_...
   MONO_WEBHOOK_SECRET=your_webhook_secret
   MONO_ENV=live
   FRONTEND_URL=https://app.troosolar.io
   ```
4. Set webhook URL (same for Connect, Credit Worthiness, Direct Debit):
   ```
   https://api.troosolar.com/api/webhooks/mono
   ```
5. Top up your **Mono wallet** (Credit Worthiness and some payment APIs bill from wallet balance).

---

## 2. Deploy backend

On production server:

```bash
cd Troosolar_backend
git pull origin master
php artisan migrate
php artisan optimize:clear
# or hit https://api.troosolar.com/api/optimize-app
```

New tables: `mono_debit_mandates`, `mono_debit_transactions`.

---

## 3. Credit check fee (user flow)

1. User chooses **Mono** path → links bank (Connect only).
2. On fee screen:
   - **Debit from linked bank** → Mono DirectPay (one-time from linked account).
   - **Flutterwave** → card / transfer fallback.
3. After fee is verified → credit check runs.

No Direct Debit mandate is required for the fee.

---

## 4. Loan repayments (Direct Debit flow)

### User steps

1. Open BNPL loan / order page → **Set up automatic repayments**.
2. Mono opens → user completes **e-mandate** (₦50 NIBSS verification transfer).
3. Bank approves mandate (often **1–72 hours**).
4. Webhooks update status to `ready_to_debit`.
5. User can:
   - Pay installment → **Debit from linked bank** in payment modal, or
   - Rely on daily auto-collection (see below).

### API endpoints

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/api/bnpl/mandate/initiate` | Start mandate (`mono_calculation_id`, optional `loan_application_id`) |
| GET | `/api/bnpl/mandate/status/{mono_calculation_id}` | Poll mandate status |
| POST | `/api/bnpl/installments/{id}/mono-debit` | Debit one installment now |

### Webhooks (Mono → TrooSolar)

Listen on `POST /api/webhooks/mono` with header `mono-webhook-secret`.

Relevant events:

- `events.mandates.approved`
- `events.mandates.ready` — mandate can be debited
- Mandate cancelled / rejected

---

## 5. Automatic daily collection

A scheduled command debits **due** installments when mandate is ready:

```bash
php artisan bnpl:collect-due-installments
```

Scheduled daily at **09:00 Africa/Lagos** via Laravel scheduler.

**Production cron** (required for scheduler):

```cron
* * * * * cd /path/to/Troosolar_backend && php artisan schedule:run >> /dev/null 2>&1
```

Dry run (no debits):

```bash
php artisan bnpl:collect-due-installments --dry-run
```

---

## 6. Requirements for customers

- Bank linked via Mono Connect (`/more?section=bankAccount` or during BNPL).
- **BVN** on user profile (used to create Mono Direct Debit customer).
- Sufficient balance on due date for auto-debit.

---

## 7. Troubleshooting

| Issue | What to check |
|-------|----------------|
| DirectPay fee fails | DirectPay enabled in Mono; linked account; wallet balance |
| Mandate stuck pending | User finished e-mandate? Bank approval can take 72h |
| Debit fails | `ready_to_debit` false — call `GET .../mandate/status/...` |
| Webhook not updating | `MONO_WEBHOOK_SECRET` matches Mono dashboard; URL reachable |
| 401 on Mono API | `MONO_SECRET_KEY` + `MONO_PUBLIC_KEY` same environment (live/test) |

---

## 8. Deploy dashboard

Deploy `Troosolar_Dashboard` so users see:

- Updated credit check fee copy (linked bank debit vs Flutterwave)
- **Automatic bank repayments** card on loan details
- **Debit from linked bank** in installment payment modal

---

## Reference

- [Mono Direct Debit overview](https://docs.mono.co/docs/payments/direct-debit/overview)
- [Mono DirectPay](https://docs.mono.co/api/directpay/initiate)
- [BNPL for lenders (Mono blog)](https://www.mono.co/blog/direct-debit-for-lenders-and-bnpl-operators)
