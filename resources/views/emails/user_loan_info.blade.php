@component('mail::message')
Hello {{ $partner->name }}

Here is the information for user **{{ $user->first_name }}** and their loan application(s).

 User Info
 First Name: {{ $user->first_name }}
 <br>
 Sur Name: {{ $user->sur_name }}
 <br>
 Email: {{ $user->email }}
 <br>
 Phone: {{ $user->phone  }}
<br>
    Credit Check 
    <br>
    Account Number: {{ $linkAccount->account_number }}
    <br>
    Account Name: {{ $linkAccount->account_name }}
    <br>
    Bank Name: {{ $linkAccount->bank_name }}
    <br>

## Kyc Details
<br>
Document
<br>
Select Document: {{ $loanApplications->title_document }}
 
Beneficiary Info
<br>
 Beneficiary Name: {{ $loanApplications->beneficiary_name }}
 <br>
 Beneficiary Email: {{ $loanApplications->beneficiary_email }}
 <br>
 Beneficiary Phone: {{ $loanApplications->beneficiary_phone  }}
 <br>
 Beneficiary Relation: {{ $loanApplications->beneficiary_relation }}
<br>
 Loan Details
 <br>
 Amount: {{ $loanApplications->loan_amount }}
 <br>
  Repayment Duration: {{ $loanApplications->repayment_duration }}
<br>

 Status: {{ $loanApplications->status }}
 <br>
 Submitted At: {{ $loanApplications->created_at }}
<br>


Thanks,  
{{-- {{ config('app.name') }} --}}
@endcomponent
