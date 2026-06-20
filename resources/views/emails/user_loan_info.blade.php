<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject ?? 'Loan application – Troosolar' }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #eef2ff; }
        .container { background-color: #f5f7ff; border-radius: 12px; padding: 32px; margin: 20px 0; border: 1px solid #e2e8f0; }
        h1 { color: #273e8e; font-size: 22px; margin-top: 0; margin-bottom: 8px; }
        h2 { color: #273e8e; font-size: 16px; margin: 0 0 12px 0; }
        h3 { color: #1e3270; font-size: 14px; margin: 16px 0 8px 0; }
        .intro { color: #444; margin: 16px 0 24px 0; }
        .details { background: #fff; border-radius: 8px; padding: 16px 20px; margin: 16px 0; font-size: 14px; border: 1px solid #e2e8f0; }
        .details p { margin: 8px 0; }
        .details ul { margin: 8px 0; padding-left: 20px; }
        .details li { margin: 6px 0; }
        .muted { color: #64748b; font-style: italic; }
        .footer { margin-top: 28px; padding-top: 20px; border-top: 1px solid #cbd5e1; font-size: 12px; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Loan application for credit evaluation</h1>

        <p>Hello {{ $partner->name }},</p>

        <div class="intro">
            <p>Please find below the <strong>customer and application details</strong> for credit evaluation.</p>
            <p><strong>Supporting files</strong> (bank statement, live selfie, KYC documents, guarantor form when available) are attached to this email.</p>
        </div>

        <div class="details">
            <h2>Application reference</h2>
            <ul>
                <li><strong>Loan application ID:</strong> {{ $loanApplication->id }}</li>
                <li><strong>Status:</strong> {{ $loanApplication->status }}</li>
                <li><strong>Submitted at:</strong> {{ $loanApplication->created_at }}</li>
            </ul>
        </div>

        <div class="details">
            <h2>Customer (profile)</h2>
            <ul>
                <li><strong>First name:</strong> {{ $user->first_name }}</li>
                <li><strong>Surname:</strong> {{ $user->sur_name }}</li>
                <li><strong>Email:</strong> {{ $user->email }}</li>
                <li><strong>Phone:</strong> {{ $user->phone }}</li>
            </ul>

            @if(!empty($finalApplicationPersonalLines))
                <h3>As submitted on final BNPL application (may differ from profile)</h3>
                <ul>
                    @foreach($finalApplicationPersonalLines as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="details">
            <h2>BNPL / product</h2>
            <ul>
                <li><strong>Customer type:</strong> {{ $loanApplication->customer_type ?? '—' }}</li>
                <li><strong>Product category:</strong> {{ $loanApplication->product_category ?? '—' }}</li>
                @if(!empty($loanApplication->audit_type))
                    <li><strong>Audit type:</strong> {{ $loanApplication->audit_type }}</li>
                @endif
            </ul>

            <h3>Product / bundle ordered</h3>
            @if(!empty($orderItemsSummary))
                <p style="white-space: pre-line; margin: 8px 0;">{{ $orderItemsSummary }}</p>
            @else
                <p class="muted">Order line snapshot not available on this application.</p>
            @endif
        </div>

        <div class="details">
            <h2>Property</h2>
            <ul>
                <li><strong>State:</strong> {{ $loanApplication->property_state ?? '—' }}</li>
                <li><strong>Address:</strong> {{ $loanApplication->property_address ?? '—' }}</li>
                <li><strong>Landmark:</strong> {{ $loanApplication->property_landmark ?? '—' }}</li>
                <li><strong>Floors:</strong> {{ $loanApplication->property_floors ?? '—' }}</li>
                <li><strong>Rooms:</strong> {{ $loanApplication->property_rooms ?? '—' }}</li>
                <li><strong>Gated estate:</strong> {{ $loanApplication->is_gated_estate ? 'Yes' : 'No' }}</li>
                @if($loanApplication->is_gated_estate)
                    <li><strong>Estate name:</strong> {{ $loanApplication->estate_name ?? '—' }}</li>
                    <li><strong>Estate address:</strong> {{ $loanApplication->estate_address ?? '—' }}</li>
                @endif
            </ul>
        </div>

        <div class="details">
            <h2>Loan plan snapshot (BNPL)</h2>
            @if(!empty($loanPlanLines))
                <ul>
                    @foreach($loanPlanLines as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            @else
                <p class="muted">No structured loan plan snapshot on file.</p>
            @endif
            <p><strong>Loan amount (stored):</strong> {{ $loanApplication->loan_amount }}</p>
            <p><strong>Repayment duration (months):</strong> {{ $loanApplication->repayment_duration }}</p>

            @if(!empty($monoSummaryLines))
                <h3>Mono / calculation (if linked)</h3>
                <ul>
                    @foreach($monoSummaryLines as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="details">
            <h2>Credit check &amp; identity</h2>
            <ul>
                <li><strong>Credit check method:</strong> {{ $loanApplication->credit_check_method ?? '—' }}</li>
                <li><strong>BVN (application):</strong> {{ $loanApplication->bvn ?? '—' }}</li>
                <li><strong>Social media (application):</strong> {{ $loanApplication->social_media_handle ?? '—' }}</li>
            </ul>

            <h3>Linked bank account (if any)</h3>
            @if($linkAccount)
                <ul>
                    <li><strong>Account number:</strong> {{ $linkAccount->account_number }}</li>
                    <li><strong>Account name:</strong> {{ $linkAccount->account_name }}</li>
                    <li><strong>Bank:</strong> {{ $linkAccount->bank_name }}</li>
                </ul>
            @else
                <p class="muted">No linked account on file.</p>
            @endif
        </div>

        <div class="details">
            <h2>Beneficiary (offer / contact)</h2>
            <ul>
                <li><strong>Name:</strong> {{ $loanApplication->beneficiary_name ?? '—' }}</li>
                <li><strong>Email:</strong> {{ $loanApplication->beneficiary_email ?? '—' }}</li>
                <li><strong>Phone:</strong> {{ $loanApplication->beneficiary_phone ?? '—' }}</li>
                <li><strong>Relationship:</strong> {{ $loanApplication->beneficiary_relationship ?? '—' }}</li>
            </ul>
        </div>

        <div class="details">
            <h2>KYC documents (paths on file)</h2>
            <ul>
                <li><strong>Title document:</strong> {{ $loanApplication->title_document ?? '—' }}</li>
                <li><strong>Upload document:</strong> {{ $loanApplication->upload_document ?? '—' }}</li>
                <li><strong>Bank statement path:</strong> {{ $loanApplication->bank_statement_path ?? '—' }}</li>
                <li><strong>Live photo path:</strong> {{ $loanApplication->live_photo_path ?? '—' }}</li>
            </ul>
        </div>

        <div class="details">
            <h2>Guarantor</h2>
            @if(!empty($guarantorSummaryLines))
                <ul>
                    @foreach($guarantorSummaryLines as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                    @if($loanApplication->guarantor && !empty($loanApplication->guarantor->signed_form_path))
                        <li><strong>Signed form file:</strong> attached as <code>guarantor_signed_form_*</code> when the file exists on the server.</li>
                    @endif
                </ul>
            @else
                <p class="muted">No guarantor record linked to this application.</p>
            @endif
        </div>

        <div class="details">
            <h2>Admin / counter-offer (if any)</h2>
            <ul>
                <li><strong>Admin notes:</strong> {{ $loanApplication->admin_notes ?? '—' }}</li>
                <li><strong>Counter-offer min deposit (stored):</strong> {{ $loanApplication->counter_offer_min_deposit ?? '—' }}</li>
                <li><strong>Counter-offer min tenor:</strong> {{ $loanApplication->counter_offer_min_tenor ?? '—' }}</li>
            </ul>
        </div>

        <p style="margin-top: 24px;">Thank you,<br><strong>Troosolar Team</strong></p>

        <div class="footer">
            <p>This is an automated message from Troosolar for financing partner review. Please do not reply to this email unless instructed by Troosolar operations.</p>
        </div>
    </div>
</body>
</html>
