@component('mail::message')
Hello {{ $partner->name }},

Please find below the **customer and application details** for credit evaluation. **Supporting files** (bank statement, live selfie, KYC documents, guarantor form when available) are attached to this email.

---

## Application reference
- **Loan application ID:** {{ $loanApplication->id }}
- **Status:** {{ $loanApplication->status }}
- **Submitted at:** {{ $loanApplication->created_at }}

---

## Customer (profile)
- **First name:** {{ $user->first_name }}
- **Surname:** {{ $user->sur_name }}
- **Email:** {{ $user->email }}
- **Phone:** {{ $user->phone }}

@if(!empty($finalApplicationPersonalLines))
### As submitted on final BNPL application (may differ from profile)
@foreach($finalApplicationPersonalLines as $line)
- {{ $line }}
@endforeach
@endif

---

## BNPL / product
- **Customer type:** {{ $loanApplication->customer_type ?? '—' }}
- **Product category:** {{ $loanApplication->product_category ?? '—' }}
@if(!empty($loanApplication->audit_type))
- **Audit type:** {{ $loanApplication->audit_type }}
@endif

@if(!empty($orderItemsSummary))
### Product / bundle ordered
{!! nl2br(e($orderItemsSummary)) !!}
@else
### Product / bundle ordered
_Order line snapshot not available on this application._
@endif

---

## Property
- **State:** {{ $loanApplication->property_state ?? '—' }}
- **Address:** {{ $loanApplication->property_address ?? '—' }}
- **Landmark:** {{ $loanApplication->property_landmark ?? '—' }}
- **Floors:** {{ $loanApplication->property_floors ?? '—' }}
- **Rooms:** {{ $loanApplication->property_rooms ?? '—' }}
- **Gated estate:** {{ $loanApplication->is_gated_estate ? 'Yes' : 'No' }}
@if($loanApplication->is_gated_estate)
- **Estate name:** {{ $loanApplication->estate_name ?? '—' }}
- **Estate address:** {{ $loanApplication->estate_address ?? '—' }}
@endif

---

## Loan plan snapshot (BNPL)
@if(!empty($loanPlanLines))
@foreach($loanPlanLines as $line)
- {{ $line }}
@endforeach
@else
_No structured loan plan snapshot on file._
@endif

**Loan amount (stored):** {{ $loanApplication->loan_amount }}  
**Repayment duration (months):** {{ $loanApplication->repayment_duration }}

@if(!empty($monoSummaryLines))
### Mono / calculation (if linked)
@foreach($monoSummaryLines as $line)
- {{ $line }}
@endforeach
@endif

---

## Credit check & identity
- **Credit check method:** {{ $loanApplication->credit_check_method ?? '—' }}
- **BVN (application):** {{ $loanApplication->bvn ?? '—' }}
- **Social media (application):** {{ $loanApplication->social_media_handle ?? '—' }}

### Linked bank account (if any)
@if($linkAccount)
- **Account number:** {{ $linkAccount->account_number }}
- **Account name:** {{ $linkAccount->account_name }}
- **Bank:** {{ $linkAccount->bank_name }}
@else
_No linked account on file._
@endif

---

## Beneficiary (offer / contact)
- **Name:** {{ $loanApplication->beneficiary_name ?? '—' }}
- **Email:** {{ $loanApplication->beneficiary_email ?? '—' }}
- **Phone:** {{ $loanApplication->beneficiary_phone ?? '—' }}
- **Relationship:** {{ $loanApplication->beneficiary_relationship ?? '—' }}

---

## KYC documents (paths on file)
- **Title document:** {{ $loanApplication->title_document ?? '—' }}
- **Upload document:** {{ $loanApplication->upload_document ?? '—' }}
- **Bank statement path:** {{ $loanApplication->bank_statement_path ?? '—' }}
- **Live photo path:** {{ $loanApplication->live_photo_path ?? '—' }}

---

## Guarantor
@if(!empty($guarantorSummaryLines))
@foreach($guarantorSummaryLines as $line)
- {{ $line }}
@endforeach
@if($loanApplication->guarantor && !empty($loanApplication->guarantor->signed_form_path))
- **Signed form file:** attached as `guarantor_signed_form_*` when the file exists on the server.
@endif
@else
_No guarantor record linked to this application._
@endif

---

## Admin / counter-offer (if any)
- **Admin notes:** {{ $loanApplication->admin_notes ?? '—' }}
- **Counter-offer min deposit (stored):** {{ $loanApplication->counter_offer_min_deposit ?? '—' }}
- **Counter-offer min tenor:** {{ $loanApplication->counter_offer_min_tenor ?? '—' }}

---

Thanks,  
{{ config('app.name') }}
@endcomponent
